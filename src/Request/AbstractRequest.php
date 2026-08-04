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

namespace Inane\Http\Request;

use Inane\Http\{
    Exception\InvalidArgumentException,
    Exception\RuntimeException,
    HttpMethod,
    Message,
    Stream,
    Uri};
use Psr\Http\Message\{
    RequestInterface,
    StreamInterface,
    UriInterface};

use function array_key_exists;
use function count;
use function is_null;
use function is_string;
use function preg_match;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;

use const false;
use const null;

/**
 * Request
 *
 * @version 0.5.4
 */
class AbstractRequest extends Message implements RequestInterface {
    /**
     * Method
     */
    private HttpMethod $method;

    /**
     * target
     */
    private ?string $requestTarget;

    /**
     * uri
     */
    private UriInterface $uri;

    /**
     * __construct
     *
     * @param null|string|HttpMethod   $method  HTTP method
     * @param null|string|UriInterface $uri     URI for the request
     * @param array                    $headers Headers to set on the request
     * @param mixed                    $body    Body of the request
     * @param ?string                  $version Protocol version
     *
     * @return void
     *
     * @throws RuntimeException InvalidArgumentException if invalid arguments are provided
     */
    public function __construct(
        null|string|HttpMethod   $method = null,
        null|string|UriInterface $uri = null,
        array                    $headers = [],
        mixed                    $body = null,
        ?string                  $version = null,
    ) {
        $this->setMethod($method);
        $this->setUri($uri);

        if (count($headers) > 0) $this->setHeaders($headers);
        if (!is_null($version)) $this->protocol = $version;

        if (!is_null($uri) && !isset($this->headerNames['host']) && count($headers) > 0) $this->updateHostFromUri();

        if (!is_null($body)) {
            if (!($body instanceof StreamInterface)) $body = new Stream($body);
            $this->stream = $body;
        }
    }

    /**
     * Sets the HTTP method for the request.
     *
     * @param null|string|HttpMethod $method The HTTP method to set. Accepts a string representation of the method,
     *                                       an instance of HttpMethod, or null to use the default method.
     *
     * @return self Returns the current instance of the class to allow for method chaining.
     */
    protected function setMethod(null|string|HttpMethod $method = null): self {
        if ($method) {
            if (is_string($method)) $this->method = HttpMethod::tryFrom(strtoupper($method));
            elseif ($method instanceof HttpMethod) $this->method = $method;
            else $this->method = HttpMethod::Get;
        } elseif (!isset($this->method)) $this->method = HttpMethod::tryFrom(array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : 'GET');

        return $this;
    }

    /**
     * Builds and returns the origin part of a URL (scheme, host, and port) based on the provided server array.
     *
     * @param array $s                  The server array, typically $_SERVER, containing request information.
     * @param bool  $use_forwarded_host Optional. Whether to use the 'X-Forwarded-Host' header if present. Default is false.
     *
     * @return string The URL origin (e.g., "https://example.com:8080").
     */
    private static function urlOrigin(array $s, bool $use_forwarded_host = false): string {
        $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] === 'on');
        $sp = strtolower($s['SERVER_PROTOCOL'] ?? 'HTTP://');
        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
        $port = $s['SERVER_PORT'] ?? '80';
        $port = ((!$ssl && $port === '80') || ($ssl && $port === '443')) ? '' : ':' . $port;
        $host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : ($s['HTTP_HOST'] ?? null);
        $host = $host ?? ($s['SERVER_NAME'] ?? 'localhost') . $port;

        return $protocol . '://' . $host;
    }

    /**
     * Builds and returns the full URL based on the provided server parameters.
     *
     * @param array $s                  The server parameters, typically from $_SERVER.
     * @param bool  $use_forwarded_host Whether to use the forwarded host (from HTTP headers) instead of the direct host.
     *
     * @return string The constructed full URL.
     */
    private static function fullUrl(array $s, bool $use_forwarded_host = false): string {
        return static::urlOrigin($s, $use_forwarded_host) . ($s['REQUEST_URI'] ?? '');
    }

    /**
     * Sets the URI for the request.
     *
     * @param null|string|UriInterface $uri  The URI to set. Accepts a string representation of the URI,
     *                                       an instance of UriInterface, or null to use the default URI.
     *
     * @return self Returns the current instance of the class to allow for method chaining.
     */
    protected function setUri(null|string|UriInterface $uri = null): self {
        if (!isset($this->uri)) {
            if (is_null($uri)) $uri = new Uri(static::fullUrl($_SERVER));
            elseif (!($uri instanceof Uri)) $uri = new Uri($uri);
            $this->uri = $uri;
        }

        return $this;
    }

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it'll appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget(): string {
        if (isset($this->requestTarget)) return $this->requestTarget;

        $target = $this->uri->getPath();
        if ($target === '') $target = '/';
        if ($this->uri->getQuery() !== '') $target .= '?' . $this->uri->getQuery();

        return $target;
    }

    /**
     * Sets the request target for the request.
     *
     * @param string $requestTarget The request target to set. Must not contain whitespace.
     *
     * @return static Returns a new instance of the class with the updated request target.
     *
     * @throws InvalidArgumentException If the request target contains whitespace.
     */
    public function withRequestTarget(string $requestTarget): static {
        if (preg_match('#\s#', $requestTarget)) throw new InvalidArgumentException(
            'Invalid request target provided; can\'t contain whitespace',
        );

        return clone($this, [
            'requestTarget' => $requestTarget,
        ]);
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @since 0.5.1
     *
     * @return HttpMethod Returns the request method.
     */
    public function getHttpMethod(): HttpMethod {
        if (!isset($this->method)) $this->setMethod();

        return $this->method;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod(): string {
        return $this->getHttpMethod()->value;
    }

    /**
     * Creates a new instance of the request with the specified HTTP method.
     *
     * @param string $method The HTTP method to set for the request.
     *
     * @return RequestInterface Returns a new instance of the request with the specified method.
     */
    public function withMethod(string $method): RequestInterface {
        $new = clone $this;
        $new->setMethod($method);

        return $new;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     *
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri(): UriInterface {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI doesn't
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI doesn't contain a
     *   host component, this method MUSTN'T update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUSTN'T update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     *
     * @param UriInterface $uri          New request URI to use.
     * @param bool         $preserveHost Preserve the original state of the Host header.
     *
     * @return static
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): static {
        if ($uri === $this->uri) return $this;

        $new = clone($this, [
            "uri" => $uri
        ]);

        if (!$preserveHost || !isset($this->headerNames['host'])) $new->updateHostFromUri();

        return $new;
    }

    /**
     * Update Host From Uri
     *
     * @return void
     */
    private function updateHostFromUri(): void {
        $host = $this->uri->getHost();

        if ($host === '') return;

        if (($port = $this->uri->getPort()) !== null) $host .= ':' . $port;

        if (isset($this->headerNames['host'])) $header = $this->headerNames['host'];
        else {
            $header = 'Host';
            $this->headerNames['host'] = 'Host';
        }
        // Ensure Host is the first header.
        // See: http://tools.ietf.org/html/rfc7230#section-5.4
        $this->headers = [$header => [$host]] + $this->headers;
    }
}
