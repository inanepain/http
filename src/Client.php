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
 * @author   Philip Michael Raab<philip@cathedral.co.za>
 * @package  inanepain\http
 * @category http
 *
 * @license  UNLICENSE
 * @license  https://unlicense.org/UNLICENSE UNLICENSE
 *
 * _version_ $version
 */

declare(strict_types = 1);

namespace Inane\Http;

use CURLFile;
use Inane\File\File;
use Inane\Stdlib\Exception\BadMethodCallException;
use Inane\Stdlib\Exception\JsonException;
use Inane\Stdlib\Exception\RuntimeException;
use Inane\Stdlib\Exception\UnexpectedValueException;
use Inane\Stdlib\Json;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface};
use SplObserver;
use SplSubject;
use Throwable;
use function array_map;
use function class_exists;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function curl_setopt_array;
use function explode;
use function fclose;
use function feof;
use function flush;
use function fopen;
use function fread;
use function fseek;
use function header;
use function http_response_code;
use function implode;
use function ini_set;
use function is_array;
use function is_string;
use function mime_content_type;
use function ob_end_flush;
use function ob_flush;
use function ob_get_level;
use function ob_start;
use function preg_match;
use function round;
use function set_time_limit;
use function str_replace;
use function stream_context_create;
use function strpos;
use function strtoupper;
use function substr;
use function trim;
use function usleep;
use const CURLINFO_HEADER_SIZE;
use const CURLINFO_HTTP_CODE;
use const CURLINFO_SIZE_DOWNLOAD;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_MAXREDIRS;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;
use const CURLOPT_USERAGENT;

/**
 * Client
 *
 * Sends Http messages
 *
 * @link    file:///Users/philip/Temp/mime/mt.php for mimetype updating
 *
 * @version 1.8.0
 */
class Client implements SplSubject, ClientInterface {
    //#region Properties
    /**
     * SplObserver[] observers
     */
    private array $observers = [];
    /**
     * List of clients to be notified
     *
     * @var array
     */
    protected array $notifyClients = [];
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
    //#endregion Properties

    /**
     * Client constructor
     */
    public function __construct() {}

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
        foreach($request->getHeaders() as $name => $values) $headers[] = "$name: " . implode(', ', $values);

        // create options
        $opts = [
            'http' => [
                'method' => $request->getMethod(),
                'header' => $headers,
            ],
        ];

        try {
            $body = $request->getBody()
                ->getContents()
            ;
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
        foreach($http_response_header ?? [] as $headerLine) {
            if (str_contains($headerLine, ':')) {
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
     * @throws JsonException
     */
    protected function curlRequest(
        string $url,
        string $method = 'GET',
        array $headers = [],
        mixed $bodyOrFile = null,  // string/array for body; array with 'file' for upload
        bool   $verifySsl = true,
    ): array {
        $ch = curl_init();

        // Basic setup
        curl_setopt_array($ch, [
            CURLOPT_URL              => $url,
            CURLOPT_RETURNTRANSFER   => true,
            CURLOPT_FOLLOWLOCATION   => true,
            CURLOPT_HEADER           => true,
            CURLOPT_MAXREDIRS        => 3,
            // Connection phase timeout (seconds)
            CURLOPT_CONNECTTIMEOUT   => 0,
            CURLOPT_TIMEOUT          => 0,
            CURLOPT_NOPROGRESS       => false,
            CURLOPT_XFERINFOFUNCTION => $this->notifyProgressClients(...),
            CURLOPT_SSL_VERIFYPEER   => $verifySsl,
            CURLOPT_SSL_VERIFYHOST   => $verifySsl ? 2 : 0,
            CURLOPT_USERAGENT        => 'PHP-cURL/1.0',
        ]);

        // Method handling
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        // Headers
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_map(static function($h) {
                return is_string($h) ? $h : "{$h['name']}: {$h['value']}";
            }, $headers));
        }

        // Body or file
        if ($bodyOrFile !== null) {
            if (is_array($bodyOrFile) && isset($bodyOrFile['file'])) {
                // Multipart file upload (e.g., ['file' => ['path' => '/file.txt', 'name' => 'upload.txt']])
                $postFields = [];
                foreach($bodyOrFile as $key => $data) {
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
                    $bodyOrFile = Json::encode($bodyOrFile);  // Auto-JSON
                    $headers[] = ['name' => 'Content-Type', 'value' => 'application/json'];
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyOrFile);
                if (strtoupper($method) !== 'GET') {
                    curl_setopt($ch, CURLOPT_POST, true);
                }
            }
        }

        // Execute
        $response = curl_exec($ch);                                       // Perform a cURL session
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);              // Get information regarding a specific transfer
        $responseSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);        // Get information regarding a specific transfer
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);           // Get information regarding a specific transfer

        // Extract the headers
        $headers_str = substr((string)$response, 0, (int)$header_size);   // Return part of a string or false on failure. For PHP8.0+ only string is returned
        $headers_str = str_replace("\r\n", "\n", $headers_str);           // Replace all occurrences of the search string with the replacement string

        // Extract the body
        $body = substr($response, $header_size);

        // You can further parse the headers into an associative array if needed
        $header_lines = explode("\n", $headers_str);
        $responseHeaders = [];
        foreach($header_lines as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $responseHeaders[trim($key)] = trim($value);
            }
        }

        // Error check
        $error = curl_error($ch);

        if ($error) {
            throw new RuntimeException("cURL Error: {$error}");
        }

        return [
            'status'  => $httpStatus,
            'body'    => $body,
            'headers' => $responseHeaders,  // Raw; parse if needed
            'size'    => $responseSize,
            'success' => $httpStatus >= 200 && $httpStatus < 300,
        ];
    }

    /**
     * send headers
     *
     * @param ResponseInterface $response
     *
     * @return void
     */
    protected function sendHeaders(ResponseInterface $response): void {
        http_response_code($response->getStatus()
            ->code());

        /**
         * @var Response $response
         */
        if ($response->getStatus() == HttpStatus::PartialContent || $response->getStatus() == HttpStatus::Ok) header($response->getStatus()
            ->message());

        foreach($response->getHeaders() as $header => $value) {
            if (is_array($value)) foreach($value as $val) header("$header: $val"); elseif ($value == '') header($header);
            else header("$header: $value");
        }
    }

    #region EVENTS

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
     * @param resource          $fp       The file pointer resource from which the buffer will be read.
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

        while(!feof($fp)) {
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
        $fp = fopen($this->servfile->getPathname(), 'rb');
        fseek($fp, $byte_from);
        $this->servProgress = $byte_from;

        ini_set('memory_limit', '-1'); // unlimited

        // if Dumper exists, lets disable it.
        if (class_exists('\Inane\Dumper\Dumper')) {
            $originalValue = \Inane\Dumper\Dumper::$enabled;
            \Inane\Dumper\Dumper::$enabled = false;
        }

        if ($response->isThrottled()) $this->sendBuffer($response, $fp); else $this->sendResponse($response->setBody(fread($fp, $byte_to)));

        // if Dumper exists, restore value.
        if (class_exists('\Inane\Dumper\Dumper')) {
            \Inane\Dumper\Dumper::$enabled = $originalValue;
        }

        fclose($fp);
    }
    #endregion EVENTS

    #region Request & progress notifications

    /**
     * Notify Progress Clients
     *
     * Notifies registered clients about the progress of a download operation.
     *
     * @param mixed $resource       The resource being downloaded.
     * @param int   $download_total The total size of the download in bytes.
     * @param int   $downloaded     The amount of data downloaded so far in bytes.
     * @param int   $upload_total   The total size of the upload in bytes.
     * @param int   $uploaded       The amount of data uploaded so far in bytes.
     *
     * @return int Always returns 0 to indicate the process status.
     */
    protected function notifyProgressClients($resource, int $download_total, int $downloaded, int $upload_total, int $uploaded): int {
        if ($download_total > 0) {
            $percent = ($downloaded / $download_total) * 100;
        } else {
            $percent = 100;
        }

        foreach($this->notifyClients as $notify) {
            if ($download_total > 0) {
                $notify->progress($download_total, $downloaded, $percent);
            }
        }

        return 0;
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
        if ($this->servProgress > $fileSize) $this->servProgress = $fileSize;

        $percent = round($this->servProgress / $fileSize * 100, 0);
        if ($percent != $this->servPercent) {
            $this->notify();
            $this->servPercent = (int)$percent;
        }

        return $this;
    }

    /**
     * Register Progress Listener
     *
     * Adds a progress listener to receive notifications if it is not already registered.
     *
     * @param NotifyProgressInterface $listener The listener to be registered.
     *
     * @return void
     */
    public function registerProgressListener(NotifyProgressInterface $listener): void {
        if (!in_array($listener, $this->notifyClients, true)) {
            $this->notifyClients[] = $listener;
        }
    }
    #endregion Request & progress notifications

    #region Client Download Progress of Served Files

    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     *
     * @return Response
     *
     * @throws BadMethodCallException
     * @throws UnexpectedValueException
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
            foreach($request->getHeaders() as $name => $values) {
                $headers[] = ['name' => $name, 'value' => implode(', ', $values)];
            }

            [
                'status'  => $statusCode,
                'body'    => $body,
                'headers' => $headers,
            ] = $this->curlRequest((string)$request->getUri(), $request->getMethod(), $headers, $request->getBody()
                ->getContents(), false);

            // set the response
            $response = $request->getResponse($body, $statusCode, $headers);
        } catch (Throwable $th) {
            $response->setBody('Error: ' . $th->getMessage());
            $response->setStatus(HttpStatus::UnknownError);
        }

        return $response;
    }

    /**
     * Attach Observer
     *
     * To receive transfer progress update notifications
     *
     * @param SplObserver $observer observe transfer progress
     *
     * @return void
     */
    public function attach(SplObserver $observer): void {
        $this->observers[] = $observer;
    }

    /**
     * Detach Observer
     *
     * To stop sending them transfer update notifications
     *
     * @param SplObserver $observer observer
     *
     * @return void
     */
    public function detach(SplObserver $observer): void {
        foreach($this->observers as $key => $oval) if ($oval === $observer) unset($this->observers[$key]);
    }

    /**
     * Notify observers
     *
     * Notification of transfer progress
     *
     * @return void
     */
    public function notify(): void {
        foreach($this->observers as $obs) $obs->update($this);
    }

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
            'total'    => $this->servfile->getSize(),
        ];
    }
    #endregion Client Download Progress of Served Files

    /**
     * serve response
     *
     * @param ResponseInterface $response response
     * @param int               $options  flags
     *
     * @return void
     */
    public function send(ResponseInterface $response): never {
        /**
         * @var Response $response
         */
        if ($response->isDownload()) $this->serveFile($response); else $this->sendResponse($response);
        exit(0);
    }
}
