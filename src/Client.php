<?php

/**
 * Inane\Http
 *
 * Http
 *
 * PHP version 8.1
 *
 * @package Inane\Http
 * @author Philip Michael Raab<peep@inane.co.za>
 *
 * @license UNLICENSE
 * @license https://github.com/inanepain/http/raw/develop/UNLICENSE UNLICENSE
 *
 * @version $Id$
 * $Date$
 */
declare(strict_types=1);

namespace Inane\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use SplObserver;
use SplSubject;
use Throwable;

use function fclose;
use function feof;
use function file_get_contents;
use function flush;
use function fopen;
use function fread;
use function fseek;
use function header;
use function http_response_code;
use function is_array;
use function method_exists;
use function ob_end_flush;
use function ob_flush;
use function ob_get_level;
use function ob_start;
use function round;
use function set_time_limit;
use function usleep;

/**
 * Client
 *
 * Sends Http messages
 *
 * @link file:///Users/philip/Temp/mime/mt.php for mimetype updating
 *
 * @package Inane\Http
 * @version 1.7.2
 */
class Client implements SplSubject, ClientInterface {
    /**
     * SplObserver[] observers
     */
    private array $observers = [];

    /**
     * File size served
     *
     * @var int
     */
    protected int $_progress = 0;

    /**
     * File size served %
     *
     * @var int
     */
    protected int $_percent = 0;

    /**
     * Client constructor
     */
    public function __construct() {
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
        $response = new Response();
        if ($request->getMethod() == 'GET') {
            try {
                $body = file_get_contents("{$request->getUri()}");
                $response->setBody($body);
                $response->setStatus(HttpStatus::Ok);
            } catch (Throwable $th) {
                $response->setBody('Error');
                $response->setStatus(HttpStatus::UnknownError);
            }
        } else {
            $response->setBody('Client does not support request type yet. Check for upgrades.');
            $response->setStatus(HttpStatus::UpgradeRequired);
        }

        if (method_exists($request, 'setResponse')) $request->setResponse($response);

        return $response;
    }

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
            'filename' => $this->_file->getFilename(),
            'progress' => $this->_progress,
            'total' => $this->_file->getSize()
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
        $this->_progress += $progress;
        if ($this->_progress > $fileSize)
            $this->_progress = $fileSize;

        $percent = round($this->_progress / $fileSize * 100, 0);
        if ($percent != $this->_percent) {
            $this->notify();
            $this->_percent = $percent;
        }
        return $this;
    }

    /**
     * send headers
     *
     * @param Response $response
     *
     * @return void
     */
    protected function sendHeaders(Response $response): void {
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
     * @param Response $response response
     * @param int $options flags
     * @return void
     */
    public function send(Response $response): void {
        if ($response->isDownload()) $this->serveFile($response);
        else $this->sendResponse($response);
        exit(0);
    }

    /**
     * Fetch request
     *
     * @param Request $request
     *
     * @since 1.7.0
     *
     * @return Response
     */
    public function fetch(Request $request): Response {
        $uri = $request->getUri();

        $body = file_get_contents($uri->__toString());

        return $request->getResponse($body);
    }

    /**
     * send response
     *
     * @param Response $response
     * @return void
     */
    protected function sendResponse(Response $response): void {
        $this->sendHeaders($response);
        echo $response->getBody();
    }

    /**
     * serve file
     *
     * stream file content
     *
     * @param Response $response
     *
     * @return void
     */
    protected function serveFile(Response $response): void {
        $file = $response->getFile();
        $byte_from = $response->getDownloadFrom();
        $byte_to = (int)$response->getHeaderLine('Content-Length');
        $fp = fopen($file->getPathname(), 'r');
        fseek($fp, $byte_from);
        $this->_progress = $byte_from;

        if ($response->isThrottled()) $this->sendBuffer($response, $fp);
        else $this->sendResponse($response->setBody(fread($fp, $byte_to)));

        fclose($fp);
    }

    /**
     * send buffered file
     *
     * Buffered streaming to restrict
     *  the transfer speed to specified limit
     *
     * @param Response $response
     * @param resource $fp
     *
     * @return void
     */
    protected function sendBuffer(Response $response, $fp): void {
        if (ob_get_level() == 0) ob_start();
        $this->sendHeaders($response);
        $sleep = $response->getSleep();
        $buffer_size = 1024 * 8; // 8kb
        $download_size = (int)$response->getHeaderLine('Content-Length');

        while (!feof($fp)) {
            set_time_limit(0);
            print(fread($fp, $buffer_size));
            ob_flush();
            flush();
            $this->updateProgress($buffer_size, $download_size);
            usleep($sleep);
        }
        ob_end_flush();
        $this->updateProgress(1, $download_size);
    }
}
