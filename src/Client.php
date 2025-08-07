<?php

/**
 * Inane: Http
 *
 * Http client, request and response objects implementing psr-7 (message interfaces).
 *
 * $Id$
 * $Date$
 *
 * PHP version 8.4
 *
 * @author Philip Michael Raab<philip@cathedral.co.za>
 * @package inanepain\http
 * @category http
 *
 * @license UNLICENSE
 * @license https://unlicense.org/UNLICENSE UNLICENSE
 *
 * @version $version
 */

declare(strict_types=1);

namespace Inane\Http;

use Deprecated;
use Inane\File\File;
use Psr\Http\Client\ClientInterface;
use SplObserver;
use SplSubject;
use Throwable;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};

use function class_exists;
use function explode;
use function fclose;
use function feof;
use function file_get_contents;
use function flush;
use function fopen;
use function fread;
use function fseek;
use function header;
use function http_response_code;
use function implode;
use function ini_set;
use function is_array;
use function ob_end_flush;
use function ob_flush;
use function ob_get_level;
use function ob_start;
use function preg_match;
use function round;
use function set_time_limit;
use function stream_context_create;
use function strpos;
use function trim;
use function usleep;

/**
 * Client
 *
 * Sends Http messages
 *
 * @link file:///Users/philip/Temp/mime/mt.php for mimetype updating
 *
 * @package Inane\Http
 * @version 1.8.0
 */
class Client implements SplSubject, ClientInterface {
    #region PROPERTIES
    /**
     * SplObserver[] observers
     */
    private array $observers = [];

    /**
     * File size served
     *
     * @var int
     */
    protected int $servProgress = 0;

    /**
     * File size served %
     *
     * @var int
     */
    protected int $servPercent = 0;

    /**
     * File served
     *
     * @var File
     */
    protected File $servfile;
    #endregion PROPERTIES

    /**
     * Client constructor
     */
    public function __construct() {
    }

    /**
     * Creates a stream context resource based on the provided HTTP request.
     *
     * This method generates and configures a context for use with stream-based HTTP operations,
     * using the details from the given RequestInterface instance.
     *
     * @param RequestInterface $request The HTTP request object containing request details.
     * 
     * @return resource The created stream context resource.
     */
    protected function createContext(RequestInterface $request) {
        // create headers
        $headers = [];
        foreach ($request->getHeaders() as $name => $values)
            $headers[] = "$name: " . implode(', ', $values);

        // create options
        $opts = [
            'http' => [
                'method' => $request->getMethod(),
                'header' => $headers,
            ],
        ];

        try {
            $body = $request->getBody()->getContents();
        } catch (\Throwable $th) {
            $body = null;
        }

        // add the body if found
        if ($body) $opts['http']['content'] = $body;

        // Create stream context
        return stream_context_create($opts);
    }

    /**
     * Parses and returns the global response headers from the HTTP response.
     *
     * @return array{0: int, 1: array<string, string>} An array containing: statusCode and the parsed global response headers.
     */
    protected function parseGlobalResponseHeaders(): array {
        // Parse headers
        $statusLine = $http_response_header[0] ?? 'HTTP/1.1 000 Unknown';
        preg_match('{HTTP/\S+ (\d+)}', $statusLine, $match);
        $statusCode = isset($match[1]) ? (int)$match[1] : 0;

        $headers = [];
        foreach ($http_response_header ?? [] as $headerLine) {
            if (strpos($headerLine, ':') !== false) {
                [$key, $value] = explode(':', $headerLine, 2);
                $headers[trim($key)] = trim($value);
            }
        }

        return [$statusCode, $headers];
    }

    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     *
     * @return Response
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request): Response {
        /**
         * @var Request $request
         */
        $response = new Response();

        try {
            // make the request
            $body = file_get_contents((string)$request->getUri(), false, $this->createContext($request));
            [$statusCode, $headers] = $this->parseGlobalResponseHeaders();

            // set the response
            $response = new Response($body, $statusCode, $headers);
            $response->setRequest($request);
        } catch (Throwable $th) {
            $response->setBody('Error: ' . $th->getMessage());
            $response->setStatus(HttpStatus::UnknownError);
        }

        return $response;
    }

    #region EVENTS
    /**
     * Attach Observer
     *
     * To receive transfer progress update notifications
     *
     * @param SplObserver $observer_in observe transfer progress
     *
     * @return void
     */
    public function attach(SplObserver $observer_in): void {
        $this->observers[] = $observer_in;
    }

    /**
     * Detach Observer
     *
     * To stop sending them transfer update notifications
     *
     * @param SplObserver $observer_in observer
     *
     * @return void
     */
    public function detach(SplObserver $observer_in): void {
        foreach ($this->observers as $key => $oval)
            if ($oval == $observer_in)
                unset($this->observers[$key]);
    }

    /**
     * Notify observers
     *
     * Notification of transfer progress
     *
     * @return void
     */
    public function notify(): void {
        foreach ($this->observers as $obs)
            $obs->update($this);
    }
    #endregion EVENTS

    #region PROGRESS
    /**
     * Progress of transfer
     *
     * Details:
     *  - filename
     *  - progress (transferred size)
     *  - total (size)
     *
     * @return array
     */
    public function getProgress(): array {
        return [
            'filename' => $this->servfile->getFilename(),
            'progress' => $this->servProgress,
            'total' => $this->servfile->getSize()
        ];
    }

    /**
     * update progress information
     *
     * @param int $progress amount complete
     * @param int $fileSize total / target size
     *
     * @return self
     */
    protected function updateProgress(int $progress, int $fileSize): self {
        $this->servProgress += $progress;
        if ($this->servProgress > $fileSize)
            $this->servProgress = $fileSize;

        $percent = round($this->servProgress / $fileSize * 100, 0);
        if ($percent != $this->servPercent) {
            $this->notify();
            $this->servPercent = (int)$percent;
        }
        return $this;
    }
    #endregion PROGRESS

    /**
     * send headers
     *
     * @param ResponseInterface $response
     *
     * @return void
     */
    protected function sendHeaders(ResponseInterface $response): void {
        /**
         * @var Response $response
         */
        if ($response->getStatus() == HttpStatus::PartialContent || $response->getStatus() == HttpStatus::Ok)
            header($response->getStatus()->message());

        http_response_code($response->getStatus()->code());

        foreach ($response->getHeaders() as $header => $value) {
            if (is_array($value)) foreach ($value as $val) header("$header: $val");
            else if ($value == '') header($header);
            else header("$header: $value");
        }
    }

    /**
     * serve response
     *
     * @param ResponseInterface $response response
     * @param int $options flags
     * 
     * @return void
     */
    public function send(ResponseInterface $response): never {
        /**
         * @var Response $response
         */
        if ($response->isDownload()) $this->serveFile($response);
        else $this->sendResponse($response);
        exit(0);
    }

    /**
     * Sends the given HTTP request and returns the corresponding response.
     * 
     * @deprecated 1.8.0 Please use `sendRequest`: a far more complete method.
     * 
     * @since 1.7.0
     *
     * @param Request $request The HTTP request to be sent.
     * @return Response The response received from the server.
     */
    #[Deprecated(message: 'Please use `sendRequest`: a far more complete method.', since: '1.8.0')]
    public function fetch(Request $request): Response {
        $uri = $request->getUri();

        $body = file_get_contents($uri->__toString());

        return $request->getResponse($body);
    }

    /**
     * send response
     *
     * @param ResponseInterface $response
     * 
     * @return void
     */
    protected function sendResponse(ResponseInterface $response): void {
        /**
         * @var Response $response
         */
        $this->sendHeaders($response);
        echo $response->getBody();
    }

    /**
     * Sends data read from the provided file pointer in chunks.
     * 
     * Buffered streaming which restricts the transfer speed to the limit specified by `Response::setBandwidth`.
     *
     * @param ResponseInterface $response The response object containing the meta data to send.
     * @param resource $fp The file pointer resource from which the buffer will be read.
     *
     * @return void
     */
    protected function sendBuffer(ResponseInterface $response, $fp): void {
        /**
         * @var Response $response
         */
        if (ob_get_level() == 0) ob_start();
        $this->sendHeaders($response);
        $sleep = $response->getSleep();
        $chunkSize = 16 * 1024; // 16 KB
        $download_size = (int)$response->getHeaderLine('Content-Length');

        while (!feof($fp)) {
            set_time_limit(0);
            print(fread($fp, $chunkSize));
            ob_flush();
            flush();
            $this->updateProgress($chunkSize, $download_size);
            usleep($sleep);
        }
        ob_end_flush();
        $this->updateProgress(1, $download_size);
    }

    /**
     * Serves a file in the HTTP response.
     *
     * This method handles the process of sending a file to the client
     * using the provided ResponseInterface instance.
     *
     * @param ResponseInterface $response The HTTP response object used to serve the file.
     *
     * @return void
     */
    protected function serveFile(ResponseInterface $response): void {
        /**
         * @var Response $response
         */
        $this->servfile = $response->getFile();
        $byte_from = $response->getDownloadFrom();
        $byte_to = (int)$response->getHeaderLine('Content-Length');
        $fp = fopen($this->servfile->getPathname(), 'r');
        fseek($fp, $byte_from);
        $this->servProgress = $byte_from;

        ini_set('memory_limit', '-1'); // unlimited

        // if Dumper exists, lets disable it.
        if (class_exists('\Inane\Dumper\Dumper')) \Inane\Dumper\Dumper::$enabled = false;

        if ($response->isThrottled()) $this->sendBuffer($response, $fp);
        else $this->sendResponse($response->setBody(fread($fp, $byte_to)));

        fclose($fp);
    }
}
