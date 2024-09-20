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

namespace Inane\Http\Request;

use function array_key_exists;
use function is_null;
use function is_string;
use function preg_match;
use function strtoupper;
use const false;
use const null;

use Inane\Http\{
    Exception\InvalidArgumentException,
    HttpMethod,
    Message,
    Stream,
    Uri
};
use Inane\Stdlib\Exception\{
    BadMethodCallException,
    UnexpectedValueException
};
use Psr\Http\Message\{
    RequestInterface,
    StreamInterface,
    UriInterface
};

/**
 * Request
 *
 * @version 0.5.4
 *
 * @package Inane\Http
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
     * Request
     *
     * @param null|string|HttpMethod                        $method  HTTP method
     * @param null|string|UriInterface             $uri     URI
     * @param array<string, string|string[]>       $headers Request headers
     * @param string|resource|StreamInterface|null $body    Request body
     * @param string|null                          $version Protocol version
     */
    public function __construct(
        null|string|HttpMethod $method = null,
        null|string|UriInterface $uri = null,
        array $headers = [],
        $body = null,
        ?string $version = null
    ) {
        $this->setMethod($method);
        $this->setUri($uri);

        if (count($headers) > 0) $this->setHeaders($headers);
        if (!is_null($version)) $this->protocol = $version;

        if (!is_null($uri) && count($headers) > 0 && !isset($this->headerNames['host'])) $this->updateHostFromUri();

        if (!is_null($body)) {
            if (!($body instanceof StreamInterface)) $body = new Stream($body);
            $this->stream = $body;
        }
    }

    /**
     * setMethod
     *
     * @param null|string|HttpMethod $method method
     *
     * @return Request request
     *
     * @throws UnexpectedValueException UnexpectedValueException
     * @throws BadMethodCallException BadMethodCallException
     */
    protected function setMethod(null|string|HttpMethod $method = null): self {
        if (!isset($this->method)) {
            if (is_null($method)) $this->method = HttpMethod::tryFrom(array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : 'GET');
            else if (is_string($method)) $this->method = HttpMethod::tryFrom(strtoupper($method));
            else if ($method instanceof HttpMethod) $this->method = $method;
            else $this->method = HttpMethod::Get;
        }
        return $this;
    }

    /**
     * setUri
     *
     * @param null|string|UriInterface $uri uri
     *
     * @return Request request
     *
     * @throws UnexpectedValueException UnexpectedValueException
     * @throws BadMethodCallException BadMethodCallException
     */
    protected function setUri(null|string|UriInterface $uri = null): self {
        if (!isset($this->uri)) {
            if (is_null($uri)) $uri = new Uri(array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : '');
            else if (!($uri instanceof Uri)) $uri = new Uri($uri);
            $this->uri = $uri;
        }
        return $this;
    }

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
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
        if (isset($this->requestTarget) && $this->requestTarget !== null) return $this->requestTarget;

        $target = $this->uri->getPath();
        if ($target === '') $target = '/';
        if ($this->uri->getQuery() != '') $target .= '?' . $this->uri->getQuery();

        return $target;
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     *
     * @return static
     */
    public function withRequestTarget($requestTarget): static {
        if (preg_match('#\s#', $requestTarget)) throw new InvalidArgumentException(
            'Invalid request target provided; cannot contain whitespace'
        );

        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @since 0.5.1
     *
     * @return \Inane\Http\HttpMethod Returns the request method.
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
	 * Return an instance with the provided HTTP method.
	 *
	 * While HTTP method names are typically all uppercase characters, HTTP
	 * method names are case-sensitive and thus implementations SHOULD NOT
	 * modify the given string.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * changed request method.
	 *
	 * @param   string  $method  Case-sensitive method.
	 *
	 * @return RequestInterface
	 *
	 * @throws \Inane\Stdlib\Exception\BadMethodCallException
	 * @throws \Inane\Stdlib\Exception\UnexpectedValueException
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
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     *
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false): static {
        if ($uri === $this->uri) return $this;

        $new = clone $this;
        $new->uri = $uri;

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

        if ($host == '') return;

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
