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
     * HTTP method used by the request.
     *
     * @var HttpMethod
     */
    private HttpMethod $method;

    /**
     * Explicit request target, otherwise derived from the URI.
     *
     * @var string|null
     */
    private ?string $requestTarget;

    /**
     * Request URI.
     *
     * @var UriInterface
     */
    private UriInterface $uri;

    /**
     * Initialise the request, using server values for an omitted method or URI.
     *
     * @param null|string|HttpMethod $method HTTP method, or null to use the server method.
     * @param null|string|UriInterface $uri Request URI, or null to derive it from server values.
     * @param array<string, string|array<array-key, string>> $headers Header names and values.
     * @param mixed $body Stream or source passed to Stream; other source types create an empty memory stream.
     * @param string|null $version Protocol version, or null to retain the default.
     *
     * @return void
     *
     * @throws RuntimeException If writing the body stream fails.
     * @throws \Inane\Stdlib\Exception\RuntimeException If URI component processing fails.
     * @throws \TypeError If the method is unsupported, a URI cannot be converted or headers have invalid types.
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
        if ($version) $this->protocol = $version;

        if ($uri && !$this->hasHeader('host') && count($headers) > 0) $this->updateHostFromUri();

        if ($body) {
            if (!($body instanceof StreamInterface)) $body = new Stream($body);
            $this->stream = $body;
        }
    }

    /**
     * Sets the HTTP method for the request.
     *
     * @param null|string|HttpMethod $method Method, or null to retain it or initialise it from server values.
     *
     * @return self This request.
     *
     * @throws \TypeError If the method cannot be resolved to an HttpMethod case.
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
     * Build the URL origin from server values.
     *
     * @param array<string, mixed> $s Server values, typically from $_SERVER.
     * @param bool $use_forwarded_host Whether to prefer HTTP_X_FORWARDED_HOST when present.
     *
     * @return string Scheme and authority, including a non-default port when using SERVER_NAME.
     *
     * @throws \TypeError If server values have incompatible types.
     */
    private static function urlOrigin(array $s, bool $use_forwarded_host = false): string {
        $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] === 'on');
        $sp = strtolower($s['SERVER_PROTOCOL'] ?? 'HTTP://');
        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
        $port = $s['SERVER_PORT'] ?? '80';
        $port = ((!$ssl && $port === '80') || ($ssl && $port === '443')) ? '' : ':' . $port;
        $host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : ($s['HTTP_HOST'] ?? null);
        // Host headers already carry their port; append it only for the server-name fallback.
        $host = $host ?? ($s['SERVER_NAME'] ?? 'localhost') . $port;

        return $protocol . '://' . $host;
    }

    /**
     * Build the full URL from server values.
     *
     * @param array<string, mixed> $s Server values, typically from $_SERVER.
     * @param bool $use_forwarded_host Whether to prefer HTTP_X_FORWARDED_HOST when present.
     *
     * @return string Origin followed by the request URI.
     *
     * @throws \TypeError If server values have incompatible types.
     */
    private static function fullUrl(array $s, bool $use_forwarded_host = false): string {
        return static::urlOrigin($s, $use_forwarded_host) . ($s['REQUEST_URI'] ?? '');
    }

    /**
     * Initialise the URI if it has not already been set.
     *
     * @param null|string|UriInterface $uri URI, or null to derive it from server values.
     *
     * @return self This request.
     *
     * @throws \Inane\Stdlib\Exception\RuntimeException If URI component processing fails.
     * @throws \TypeError If a non-Uri instance is passed to the Uri constructor or server values have invalid types.
     */
    protected function setUri(null|string|UriInterface $uri = null): self {
        // This initialiser leaves an existing URI unchanged; withUri() replaces it.
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
     * @return string Explicit target or the URI path and query, with '/' as the default path.
     */
    public function getRequestTarget(): string {
        if (isset($this->requestTarget)) return $this->requestTarget;

        // Origin-form targets include the path and query, but not the URI fragment.
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
     * @return HttpMethod Returns the request method.
     *
     * @throws \TypeError If an uninitialised method cannot be resolved from server values.
     */
    public function getHttpMethod(): HttpMethod {
        if (!isset($this->method)) $this->setMethod();

        return $this->method;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     *
     * @throws \TypeError If an uninitialised method cannot be resolved from server values.
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
     *
     * @throws \TypeError If the method cannot be resolved to an HttpMethod case.
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
     * @return static This request if the URI is unchanged, otherwise a clone with the new URI.
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): static {
        if ($uri === $this->uri) return $this;

        $new = clone($this, [
            "uri" => $uri
        ]);

        if (!$preserveHost || !$this->hasHeader('host')) $new->updateHostFromUri();

        return $new;
    }

    /**
     * Replace the Host header with the URI host and optional port.
     *
     * Retain the existing header when the URI has no host.
     *
     * @return void
     */
    private function updateHostFromUri(): void {
        $host = $this->uri->getHost();

        // A relative URI does not provide an authority to replace the Host header.
        if ($host === '') return;

        if (($port = $this->uri->getPort()) !== null) $host .= ':' . $port;

        $header = $this->getHeaderObject('host');
        $header->setValue($host, true);

        if (!$this->hasHeader($header->key)) $this->headers[$header->key] = $header;
    }
}
