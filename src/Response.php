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

use Inane\File\File;
use SimpleXMLElement;
use Stringable;
use Inane\Stdlib\{
    Exception\BadMethodCallException,
    Exception\UnexpectedValueException,
    Json,
    Options
};
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface,
    StreamInterface
};

use function htmlspecialchars;
use function in_array;
use function is_null;
use function is_numeric;

use const null;

/**
 * Response
 *
 * HTTP Response to a request.
 * Generally with data in the body.
 *
 * @version 0.6.3
 */
class Response extends Message implements ResponseInterface, Stringable {
    public static int $rm = 4;

    /**
     * response body
     */
    protected string $body;

    /**
     * Http Status
     */
    protected HttpStatus $status;

    /**
     * request
     */
    protected RequestInterface $request;

    /**
     * Number of seconds to delay the response between buffered output.
     *
     * @var int $_sleep microseconds
     */
    protected int $_sleep = 0;

    /**
     * Size of download
     */
    protected int $_downloadSize = 0;

    /**
     * Start serving file from
     */
    protected int $_downloadStart = 0;

    /**
     * File to serve
     */
    private File $_file;

    /**
     * Response as string
     *
     * @return string
     */
    public function __toString(): string {
        return (string)$this->getBody();
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface {
        $new = clone $this;
        $new->setStatus($code);
        return $new;
    }

    public function getReasonPhrase(): string {
        return $this->getStatus()->message();
    }

    /**
     * set: request
     *
     * @param RequestInterface $request request
     * @return Response response
     */
    public function setRequest(RequestInterface $request): self {
        if (!isset($this->request)) $this->request = $request;
        return $this;
    }

    /**
     * get: request
     *
     * @return Request request
     */
    public function getRequest(): Request {
        if (!isset($this->request)) $this->request = new Request(allowAllProperties: true, response: $this);
        return $this->request;
    }

    /**
     * Response
     *
     * @param string|StreamInterface|null $body    Request body
     * @param int|HttpStatus $status
     * @param array $headers headers
     *
     * @return void
     *
     * @throws UnexpectedValueException
     * @throws BadMethodCallException
     */
    public function __construct(string|null|StreamInterface $body = null, int|HttpStatus $status = 200, array $headers = []) {
        if (!is_null($body)) {
            if (!($body instanceof StreamInterface)) $body = new Stream($body);
            $this->stream = $body;
        }
        $this->setHeaders($headers);
        $this->setStatus($status);
    }

    /**
     * Create response from array
     *
     * @param array $array
     * @return Response
     */
    public static function fromArray(array $array): Response {
        $opt = new Options($array);
        $response = new static($opt->get('body', ''), $opt->get('status', 200), $opt->get('headers', []));
        if ($opt->offsetExists('request')) $response->setRequest($opt->get('request'));
        return $response;
    }

    /**
     * array to xml
     *
     * @param array $data
     * @param SimpleXMLElement $xml_data
     * @return void
     */
    protected function arrayToXml($data, SimpleXMLElement &$xml_data): void {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) $key = 'item' . $key;
                $subnode = $xml_data->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }

    /**
     * add header
     *
     * @param string $name
     * @param mixed $value
     * @param bool $replace
     * @return Response
     */
    public function addHeader(string $name, mixed $value, bool $replace = true): self {
        $normalized = strtolower($name);

        if (isset($this->headerNames[$normalized])) {
            $name = $this->headerNames[$normalized];
            $this->headers[$name] = array_merge($this->headers[$name], $value);
        } else {
            $this->headerNames[$normalized] = $name;
            $this->headers[$name] = $value;
        }

        $this->headers[$name] = [$value];
        return $this;
    }

    /**
     * get: status
     *
     * @param HttpStatus|int $status
     * @return Response
     * @throws UnexpectedValueException
     * @throws BadMethodCallException
     */
    public function setStatus(HttpStatus|int $status): self {
        if ($status instanceof HttpStatus) $this->status = $status;
        else $this->status = HttpStatus::from($status);
        return $this;
    }

    /**
     * get: status
     *
     * @return HttpStatus status
     */
    public function getStatus(): HttpStatus {
        return $this->status;
    }

    /**
     * set: status code
     *
     * @param mixed $statusCode
     *
     * @return self
     *
     * @throws UnexpectedValueException
     * @throws BadMethodCallException
     * @deprecated 0.5.0
     */
    public function setStatusCode(HttpStatus $statusCode): self {
        return $this->setStatus($statusCode);
    }

    /**
     * get: status code
     *
     * @return int
     */
    public function getStatusCode(): int {
        return $this->getStatus()->code();
    }

    /**
     * set body
     *
     * @param string $body
     * @return self
     */
    public function setBody(string $body): self {
        $this->stream = new Stream($body);
        return $this;
    }

    /**
     * get body
     *
     * @return string body
     */
    public function getContents(): string {
        $body = $this->getBody()->getContents();
        if (in_array($this->getHeaderLine('Content-Type'), ['application/json', '*/*']))
            return Json::encode($body);
        else if (in_array($this->getHeaderLine('Content-Type'), ['application/xml'])) {
            $xml = new SimpleXMLElement('<root/>');
            $this->arrayToXml($body, $xml);
            return $xml->asXML();
        }

        return $body;
    }

    /**
     * download response
     *
     * @return bool
     */
    public function isDownload(): bool {
        return isset($this->_file);
    }

    /**
     * force download
     *
     * @return bool
     */
    public function isForceDownload(): bool {
        return $this->getHeaderLine('Content-Description') == 'File Transfer' ? true : false;
    }

    /**
     * throttled download
     *
     * @return bool
     */
    public function isThrottled(): bool {
        return $this->_sleep > 0 ? true : false;
    }

    /**
     * file
     *
     * @return File
     */
    public function getFile(): File {
        return $this->_file;
    }

    /**
     * download start position
     *
     * @return int
     */
    public function getDownloadFrom(): int {
        return $this->_downloadStart;
    }

    /**
     * sleep delay between buffers
     *
     * @return int microseconds
     */
    public function getSleep(): int {
        return $this->_sleep;
    }

    /**
     * Sets download limit (0 = unlimited).
     *
     * This is a rough kb/s speed (But very rough!).
     *
     * @param  $kbps
     * 
     * @return Response
     */
    protected function setBandwidth(int $kbps = 0): self {
        if ($kbps > 0) {
            // $bytesPerSecond = ($kbps * 1024) / 8;
            // $bytesPerSecond = ($kbps * 1024);
            $bytesPerSecond = ($kbps * 1024) / static::$rm;
            $chunkSize = 16 * 1024; // 16 KB
            $this->_sleep = (int)(($chunkSize / $bytesPerSecond) * 1_000_000);
        } else $this->_sleep = 0;

        // if (static::$rm > 0) $kbps = $kbps / static::$rm;
        // $_sleep = $kbps * 4.3;
        // if ($_sleep > 0)
        //     $_sleep = (8 / $_sleep) * 1e6;

        // $this->_sleep = (int) $_sleep;

        return $this;
    }

    /**
     * gets download limit 0 = unlimited
     *
     * This is a rough kb/s speed. But very rough
     *
     * @return int kbSec
     */
    public function getBandwidth(): int {
        $chunkSize = 16 * 1024; // 16 KB
        $bytesPerSecond = $chunkSize / ($this->_sleep / 1_000_000);
        return ($bytesPerSecond * static::$rm) / 1024;
        // return ($bytesPerSecond * 8) / 1024;
        // return ($bytesPerSecond) / 1024;

        // if (is_null($sleep)) $sleep = $this->getSleep();
        // if ($sleep > 0)
        //     $sleep = (8 / ($sleep / 1e6)) / 4.3;
        // if (static::$rm > 0) $sleep = $sleep * static::$rm;
        // return $sleep;
    }

    /**
     * Set file to download
     *
     * $speed 0 = no limit
     *
     * @param null|string $src_file file
     * @param bool $force download, not view in browser
     * @param int $speed kbSec
     *
     * @return Response
     *
     * @throws UnexpectedValueException
     * @throws BadMethodCallException
     */
    public function setFile(?string $src_file, bool $force = false, int $speed = 0): self {
        $file = new File($src_file);

        if (!$file->isValid()) {
            $this->setStatus(HttpStatus::NotFound);
            $this->setBody('file invalid:' . $file->getPathname());
            return $this;
        }

        // only set file property once we know it's valid
        $this->_file = $file;

        $this->setStatus(HttpStatus::Ok);
        $fileSize = $this->_file->getSize();
        $this->_downloadSize = $fileSize;
        $this->_downloadStart = 0;

        if ($this->getRequest()->range != null) $this->updateRange();
        $this->updateFileHeaders();
        if ($force) $this->forceDownload();
        $this->setBandwidth($speed);

        return $this;
    }

    /**
     * update headers for file downloads
     *
     * @return void
     */
    protected function updateFileHeaders() {
        $this->addHeader('Accept-Ranges', 'bytes');
        $this->addHeader('Content-type', $this->_file->getMimetype() ?? 'application/octet-stream');
        $this->addHeader("Pragma", "no-cache");
        $this->addHeader('Cache-Control', 'public, must-revalidate, max-age=0');
        $this->addHeader("Content-Length", $this->_downloadSize);
    }

    /**
     * update range headers for downloads
     *
     * @return void
     */
    protected function updateRange() {
        $req = explode('=', $this->getRequest()->range);
        $ranges = explode(',', $req[1]);
        $ranges = explode('-', $ranges[0]);

        $fileSize = $this->_file->getSize();

        $start = (int) $ranges[0];
        $stop = (int) ($ranges[1] == '' ? $fileSize - 1 : $ranges[1]);

        $this->_downloadSize = $stop - $start + 1;
        $this->_downloadStart = $start;
        $downloadRange = "bytes {$start}-{$stop}/{$fileSize}";

        $this->setStatus(HttpStatus::PartialContent);
        $this->addHeader('Content-Range', $downloadRange);
    }

    /**
     * update headers for forced downloads
     *
     * @return void
     */
    protected function forceDownload() {
        $this->addHeader("Content-Description", 'File Transfer');
        $this->addHeader('Content-Disposition', 'attachment; filename="' . $this->_file->getFilename() . '";');
        $this->addHeader("Content-Transfer-Encoding", "binary");
    }
}
