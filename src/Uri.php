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

use Inane\Http\Exception\InvalidArgumentException;
use Inane\Stdlib\Options;
use Psr\Http\Message\UriInterface;

use function is_null;
use function parse_str;
use function parse_url;
use function strtolower;

/**
 * AbstractUri
 *
 * @version 0.6.2
 *
 * @package Http
 */
class Uri implements UriInterface {
    /**
     * default ports
     */
    protected const DEFAULT_PORTS = [
        'http'  => 80,
        'https' => 443,
        'ftp' => 21,
        'gopher' => 70,
        'nntp' => 119,
        'news' => 119,
        'telnet' => 23,
        'tn3270' => 23,
        'imap' => 143,
        'pop' => 110,
        'ldap' => 389,
    ];

    /** @var string|null String representation */
    protected ?string $composedComponents;

    /**
     * Uri components
     */
    protected Options $components;

    /**
     * Uri
     *
     * @param string $uri
     * @return void
     */
    public function __construct(string $uri = '') {
        $this->components = new Options([
            'scheme' => '',
            'host' => '',
            'port' => null,
            'user' => '',
            'pass' => '',
            'path' => '',
            'query' => '',
            'fragment' => '',
            'params' => [],
            'userinfo' => '',
        ]);

        if ($this->setComponents($uri) !== false) $this->verify();
    }

    /**
     * set the component parts from a uri
     *
     * @param string $uri url
     * @return bool parse result
     */
    protected function setComponents(string $uri): bool {
        $parts = parse_url($uri);
        if ($parts === false) return false;

        $query = $parts['query'] ?? '';
        parse_str($query, $params);

        $parts['params'] = $params;
        $parts['scheme'] = strtolower($parts['scheme'] ?? '');
        $parts['host'] = strtolower($parts['host'] ?? '');

        $this->composedComponents = $uri;
        $this->components->merge(new Options($parts));
        return true;
    }

    /**
     * verify components and update uri
     *
     * @return void
     *
     * @throws \Inane\Stdlib\Exception\RuntimeException
     */
    protected function verify(): void {
        $port = $this->components->port;
        $scheme = $this->components->scheme;
        if (!is_null($port) && (self::DEFAULT_PORTS[$scheme] == $port)) $this->components->set('port', null);

        if ($this->components->user != '') {
            $ui = $this->components->user;
            if ($this->components->pass != '') $ui .= ":{$this->components->pass}";
            $this->components->userinfo = $ui;
        }

        if ($this->composedComponents == null) $this->composedComponents = static::composeComponents(
            $this->components->scheme,
            $this->getAuthority(),
            $this->components->path,
            $this->components->query,
            $this->components->fragment
        );
    }

    /**
     * Composes a URI reference string from its various components.
     *
     * Usually this method does not need to be called manually but instead is used indirectly via
     * `Psr\Http\Message\UriInterface::__toString`.
     *
     * PSR-7 UriInterface treats an empty component the same as a missing component as
     * getQuery(), getFragment() etc. always return a string. This explains the slight
     * difference to RFC 3986 Section 5.3.
     *
     * Another adjustment is that the authority separator is added even when the authority is missing/empty
     * for the "file" scheme. This is because PHP stream functions like `file_get_contents` only work with
     * `file:///myfile` but not with `file:/myfile` although they are equivalent according to RFC 3986. But
     * `file:///` is the more common syntax for the file scheme anyway (Chrome for example redirects to
     * that format).
     *
     * @link https://tools.ietf.org/html/rfc3986#section-5.3
     */
    public static function composeComponents(?string $scheme, ?string $authority, string $path, ?string $query, ?string $fragment): string {
        $uri = '';

        // weak type checks to also accept null until we can add scalar type hints
        if ($scheme != '') $uri .= $scheme . ':';
        if ($authority != '' || $scheme === 'file') $uri .= '//' . $authority;

        $uri .= $path;

        if ($query != '') $uri .= '?' . $query;
        if ($fragment != '') $uri .= '#' . $fragment;

        return $uri;
    }

    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme(): string {
        return $this->components->scheme;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * todo: getAuthority
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority(): string {
        $authority = $this->components->host;
        if ($this->components->userinfo !== '') $authority = $this->components->userinfo . '@' . $authority;
        if ($this->components->port !== null) $authority .= ':' . $this->components->port;

        return $authority;
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * todo: getUserInfo
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo(): string {
        return $this->components->userinfo;
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost(): string {
        return $this->components->host;
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort(): ?int {
        return $this->components->port;
    }

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath(): string {
        return $this->components->path;
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery(): string {
        return $this->components->query;
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment(): string {
        return $this->components->fragment;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return static A new instance with the specified scheme.
     * @throws InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme): UriInterface {
        $scheme = strtolower($scheme);

        if ($this->components->scheme === $scheme) return $this;

        $new = clone $this;
        $new->components->scheme = $scheme;
        $new->composedComponents = null;
        $new->verify();
        return $new;
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return static A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null): UriInterface {
        $info = $user;
        if ($password !== null) $info .= ':' . $password;

        if ($this->components->userinfo === $info) return $this;

        $new = clone $this;
        $new->components->userinfo = $info;
        $new->components->user = $user;
        $new->components->pass = $password ?? '';
        $new->composedComponents = null;
        $new->verify();
        return $new;
    }

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     *
     * @return static A new instance with the specified host.
     *
     * @throws InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host): UriInterface {
        if ($this->components->host === $host) return $this;

        $new = clone $this;
        $new->components->host = $host;
        $new->composedComponents = null;
        $new->verify();
        return $new;
    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     *
     * @return static A new instance with the specified port.
     *
     * @throws InvalidArgumentException for invalid ports.
     */
    public function withPort($port): UriInterface {
        if ($this->components->port === $port) return $this;

        $new = clone $this;
        $new->components->port = $port;
        $new->composedComponents = null;
        $new->verify();
        return $new;
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     *
     * @return static A new instance with the specified path.
     *
     * @throws InvalidArgumentException for invalid paths.
     */
    public function withPath($path): UriInterface {
        if ($this->components->path === $path) return $this;

        $new = clone $this;
        $new->components->path = $path;
        $new->composedComponents = null;
        $new->verify();
        return $new;
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     *
     * @return static A new instance with the specified query string.
     *
     * @throws InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query): UriInterface {
        if ($this->components->query === $query) return $this;

        $query = $query ?? '';
        parse_str($query, $params);

        $new = clone $this;
        $new->components->query = $query;
        $new->components->params = $params;
        $new->composedComponents = null;
        $new->verify();
        return $new;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     * @return static A new instance with the specified fragment.
     */
    public function withFragment($fragment): UriInterface {
        if ($this->components->fragment === $fragment) return $this;

        $new = clone $this;
        $new->components->fragment = $fragment;
        $new->composedComponents = null;
        $new->verify();
        return $new;
    }

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString(): string {
        if ($this->composedComponents === null)
            $this->composedComponents = self::composeComponents(
                $this->components->scheme,
                $this->getAuthority(),
                $this->components->path,
                $this->components->query,
                $this->components->fragment
            );

        return $this->composedComponents;
    }
}
