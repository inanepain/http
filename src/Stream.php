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
 * @copyright 2013-2019 Philip Michael Raab <peep@inane.co.za>
 */

declare(strict_types=1);

namespace Inane\Http;

use Inane\Http\Exception\RuntimeException;
use Psr\Http\Message\StreamInterface;
use Stringable;
use Throwable;

use function array_key_exists;
use function fclose;
use function feof;
use function fopen;
use function fstat;
use function ftell;
use function is_null;
use function is_resource;
use function is_string;
use function preg_match;
use function sprintf;
use function stream_get_contents;
use function stream_get_meta_data;
use function trigger_error;
use const E_USER_ERROR;
use const false;
use const null;
use const PHP_VERSION_ID;
use const SEEK_SET;
use const true;

/**
 * AbstractStream
 *
 * @version 0.5.3
 *
 * @package Http
 */
class Stream implements StreamInterface, Stringable {
    protected static string $readableModes = '/r|a\+|ab\+|w\+|wb\+|x\+|xb\+|c\+|cb\+/';
    protected static string $writableModes = '/a|w|r\+|rb\+|rw|x|c/';

    /** @var resource */
    protected $stream;

    /** @var int|null */
    protected ?int $size = null;

    /** @var bool */
    protected bool $seekable;

    /** @var bool */
    protected bool $readable;

    /** @var bool */
    protected bool $writable;

    /**
     * Stream
     *
     * if string a memory stream is created
     *
     * @param mixed|null $source string or resource
     *
     * @return void
     *
     * @throws RuntimeException
     */
    public function __construct($source = null) {
        if (is_resource($source)) $this->stream = $source;
        else {
            $this->stream = fopen('php://memory', 'r+');
            if (is_string($source)) $this->write($source);
            $this->getSize();
        }
    }

    /**
     * Closes the stream when the destructed
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     *
     * @return string
     */
    public function __toString(): string {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (Throwable $e) {
            if (PHP_VERSION_ID >= 70400) throw $e;
            trigger_error(sprintf('%s::__toString exception: %s', self::class, (string) $e), E_USER_ERROR);
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close() {
        if (isset($this->stream)) {
            if (is_resource($this->stream)) fclose($this->stream);
            $this->detach();
        }
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach() {
        if (!isset($this->stream)) return null;

        $result = $this->stream;
        unset($this->stream);
        $this->size = $this->uri = null;
        $this->readable = $this->writable = $this->seekable = false;

        return $result;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize() {
        if (is_null($this->size)) {
            $stat = fstat($this->stream);
            if ($stat) $this->size = $stat['size'];
        }

        return $this->size;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws RuntimeException on error.
     */
    public function tell(): int {
        if (!isset($this->stream)) throw new RuntimeException('Stream is detached');

        $result = ftell($this->stream);

        if ($result === false) throw new RuntimeException('Unable to determine stream position');

        return $result;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof() {
        if (isset($this->stream)) return feof($this->stream);
        return true;
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable() {
        if (!isset($this->seekable)) $this->seekable = $this->getMetadata('seekable');
        return $this->seekable;
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     *
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     *
     * @throws RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET) {
        $whence = (int) $whence;

        if (!isset($this->stream)) throw new RuntimeException('Stream is detached');
        if (!$this->isSeekable()) throw new RuntimeException('Stream is not seekable');
        if (fseek($this->stream, $offset, $whence) === -1) throw new RuntimeException('Unable to seek to stream position ' . $offset . ' with whence ' . var_export($whence, true));
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     *
     * @throws RuntimeException on failure.
     */
    public function rewind() {
        if ($this->isSeekable()) $this->seek(0);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable() {
        if (!isset($this->writable)) $this->writable = (bool)preg_match(static::$writableModes, $this->getMetadata('mode'));
        return $this->writable;
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     *
     * @return int Returns the number of bytes written to the stream.
     *
     * @throws RuntimeException on failure.
     */
    public function write($string) {
        if (!isset($this->stream)) throw new RuntimeException('Stream is detached');
        if (!$this->isWritable()) throw new RuntimeException('Cannot write to a non-writable stream');

        // We can't know the size after writing anything
        $this->size = null;
        $result = fwrite($this->stream, $string);

        if ($result === false) throw new RuntimeException('Unable to write to stream');

        return $result;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable() {
        if (!isset($this->readable)) $this->readable = (bool)preg_match(static::$readableModes, $this->getMetadata('mode'));
        return $this->readable;
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     *
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     *
     * @throws RuntimeException if an error occurs.
     */
    public function read($length): string {
        if (!isset($this->stream)) throw new RuntimeException('Stream is detached');
        if (!$this->isReadable()) throw new RuntimeException('Cannot read from non-readable stream');
        if ($length < 0) throw new RuntimeException('Length parameter cannot be negative');

        if (0 === $length) return '';

        $string = fread($this->stream, $length);
        if (false === $string) throw new RuntimeException('Unable to read from stream');

        return $string;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     *
     * @throws RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents() {
        if (!isset($this->stream)) throw new RuntimeException('Stream is detached');

        $this->rewind();
        $contents = stream_get_contents($this->stream);

        if ($contents === false) throw new RuntimeException('Unable to read stream contents');

        return $contents;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     *
     * @param string $key Specific metadata to retrieve.
     *
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null) {
        if (!isset($this->stream)) return $key ? null : [];

        $meta = stream_get_meta_data($this->stream);
        if (is_null($key)) return $meta;
        else if (array_key_exists($key, $meta)) return $meta[$key];
        return null;
    }
}
