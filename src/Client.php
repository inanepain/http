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
 * _version_ $version
 */

declare(strict_types=1);

namespace Inane\Http;

use CURLFile;
use Deprecated;
use Inane\File\File;
use Inane\Stdlib\Exception\RuntimeException;
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
	 * Perform an HTTP request using cURL
	 * This method executes an HTTP request to the specified URL using the provided
	 * method, headers, body, and configurations.
	 *
	 * @param string $url        The target URL for the HTTP request
	 * @param string $method     The HTTP method to use (e.g., 'GET', 'POST', 'PUT', etc.); defaults to 'GET'
	 * @param array  $headers    An array of HTTP headers to include in the request
	 * @param mixed  $bodyOrFile The body content or file to be sent; supports a string/array for body
	 *                           or an upload array (e.g., ['file' => ['path' => ..., 'name' => ...]])
	 * @param bool   $verifySsl  Whether to verify SSL certificates and host; defaults to true
	 *
	 * @return array An array containing the response data:
	 *               - 'status': The HTTP status code of the response
	 *               - 'body': The response body as a string
	 *               - 'headers': The response headers as an associative array
	 *               - 'size': The size of the response body
	 *               - 'success': True if the response status code is 2xx, false otherwise
	 *
	 * @throws RuntimeException If a cURL error occurs during the request
	 */
	protected function curlRequest(
        string $url,
        string $method = 'GET',
        array $headers = [],
        $bodyOrFile = null,  // string/array for body; array with 'file' for upload
        bool $verifySsl = true
    ): array {
        $ch = curl_init();

        // Basic setup
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
            CURLOPT_USERAGENT => 'PHP-cURL/1.0',
        ]);

        // Method handling
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        // Headers
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_map(function ($h) {
                return is_string($h) ? $h : "{$h['name']}: {$h['value']}";
            }, $headers));
        }

        // Body or file
        if ($bodyOrFile !== null) {
            if (is_array($bodyOrFile) && isset($bodyOrFile['file'])) {
                // Multipart file upload (e.g., ['file' => ['path' => '/file.txt', 'name' => 'upload.txt']])
                $postFields = [];
                foreach ($bodyOrFile as $key => $data) {
                    if ($key === 'file') {
                        $postFields[$data['name']] = new CURLFile($data['path'], mime_content_type($data['path']), $data['name']);
                    } else {
                        $postFields[$key] = $data;
                    }
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                curl_setopt($ch, CURLOPT_POST, true);
            } else {
                // Raw body (string/JSON/array)
                if (is_array($bodyOrFile)) {
                    $bodyOrFile = json_encode($bodyOrFile);  // Auto-JSON
                    $headers[] = ['name' => 'Content-Type', 'value' => 'application/json'];
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyOrFile);
                if (strtoupper($method) !== 'GET') {
                    curl_setopt($ch, CURLOPT_POST, true);
                }
            }
        }

        // Execute
        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        // Extract the headers
        $headers = substr($response, 0, $header_size);

        // Extract the body
        $body = substr($response, $header_size);

        // You can further parse the headers into an associative array if needed
        $header_lines = explode("\r\n", $headers);
        $responseHeaders = [];
        foreach ($header_lines as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $responseHeaders[trim($key)] = trim($value);
            }
        }

        // Error check
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("cURL Error: {$error}");
        }

        return [
            'status' => $httpStatus,
            'body' => $body,
            'headers' => $responseHeaders,  // Raw; parse if needed
            'size' => $responseSize,
            'success' => $httpStatus >= 200 && $httpStatus < 300,
        ];
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
            //            $body = file_get_contents((string)$request->getUri(), false, $this->createContext($request));
            //            [$statusCode, $headers] = $this->parseGlobalResponseHeaders();

            $headers = [];
            foreach ($request->getHeaders() as $name => $values) {
                $headers[] = ['name' => $name, 'value' => implode(', ', $values)];
            }

            [
                'status' => $statusCode,
                'body' => $body,
                'headers' => $headers,
            ] = $this->curlRequest((string)$request->getUri(), $request->getMethod(), $headers, $request->getBody()->getContents(), false);

            // set the response
            $response = $request->getResponse($body, $statusCode, $headers);
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
        http_response_code($response->getStatus()->code());
        
        /**
         * @var Response $response
         */
        if ($response->getStatus() == HttpStatus::PartialContent || $response->getStatus() == HttpStatus::Ok)
            header($response->getStatus()->message());

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
