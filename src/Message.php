<?php

/**
 * Inane: Http
 *
 * Http client, request and response objects implementing psr-7 (message interfaces).
 *
 * $Id$
 * $Date$
 *
 * PHP version 8.5
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

use Psr\Http\Message\{
    MessageInterface,
    StreamInterface};

/**
 * Message
 *
 * @version 0.6.3
 */
class Message implements MessageInterface {
    /**
     * HTTP/1.0 protocol version.
     *
     * @var string
     */
    const string VERSION_10 = '1.0';

    /**
     * HTTP/1.1 protocol version.
     *
     * @var string
     */
    const string VERSION_11 = '1.1';

    /**
     * HTTP/2 protocol version.
     *
     * @var string
     */
    const string VERSION_2  = '2';

    /**
     * Message headers keyed by normalised names.
     *
     * @var array<string, Header>
     */
    protected array $headers = [];

    /**
     * HTTP protocol version without the protocol name.
     *
     * @var string
     */
    protected string $protocol = self::VERSION_11;

    /**
     * Message body stream, initialised on first access if absent.
     *
     * @var StreamInterface|null
     */
    protected ?StreamInterface $stream;

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion(): string {
        return $this->protocol;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     *
     * @return static
     */
    public function withProtocolVersion(string $version): MessageInterface {
        if ($this->protocol === $version) return $this;

        return clone($this, [
            'protocol' => $version
        ]);
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return array<string, array<array-key, string>> Header values keyed by original names.
     */
    public function getHeaders(): array {
        $headers = [];
        // Export original header names rather than normalised lookup keys.
        foreach($this->headers as $header) $headers += $header->toArray();
        return $headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     *
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader(string $name): bool {
        return isset($this->headers[Header::normalise($name)]);
    }

    /**
     * Retrieve a header object or create an empty, unstored header if absent.
     *
     * @param string $name Case-insensitive header field name.
     *
     * @return Header Stored header object or a new empty header.
     */
    public function getHeaderObject(string $name): Header {
        // Missing headers are not added to the message by a lookup.
        if (!$this->hasHeader($name)) return new Header($name);
        return $this->headers[Header::normalise($name)];
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     *
     * @return array<array-key, string> An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader(string $name): array {
        return $this->getHeaderObject($name)->getValue();
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header doesn't appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     *
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine(string $name): string {
        return $this->getHeaderObject($name)->getLine();
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string          $name  Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     *
     * @return static
     *
     * @throws \TypeError If the value is neither a string nor an array.
     */
    public function withHeader(string $name, $value): MessageInterface {
        $new = clone $this;
        $new->headers[Header::normalise($name)] = $new->getHeaderObject($name)->setValue($value, true);

        return $new;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it'll be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param string          $name  Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     *
     * @return static
     *
     * @throws \TypeError If the value is neither a string nor an array.
     */
    public function withAddedHeader(string $name, $value): MessageInterface {
        $new = clone $this;
        $new->headers[Header::normalise($name)] = $new->getHeaderObject($name)->setValue($value);

        return $new;
    }

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     *
     * @return static
     */
    public function withoutHeader(string $name): MessageInterface {
        $new = clone $this;
        unset($new->headers[Header::normalise($name)]);

        return $new;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody(): StreamInterface {
        // Create an empty memory stream only when the body is first requested.
        if (!isset($this->stream)) $this->stream = new Stream();

        return $this->stream;
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     *
     * @return static
     */
    public function withBody(StreamInterface $body): MessageInterface {
        if ($body === $this->getBody()) return $this;

        return clone($this, [
            'stream' => $body
        ]);
    }

    /**
     * Replace all headers, merging values for case-insensitive name matches.
     *
     * @param array<string, string|array<array-key, string>> $headers Header names and values.
     *
     * @return void
     *
     * @throws \TypeError If a header name is not a string or a value is neither a string nor an array.
     */
    protected function setHeaders(array $headers): void {
        $this->headers = [];
        foreach($headers as $name => $value) {
            // Reuse a stored header so differently cased names merge their values.
            $header = $this->getHeaderObject($name);
            $header->setValue($value);

            $this->headers[$header->key] = $header;
        }
    }
}
