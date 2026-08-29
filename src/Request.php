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

use Inane\Http\Exception\PropertyException;
use Inane\Http\Exception\RuntimeException;
use Inane\Http\Request\AbstractRequest;
use Inane\Stdlib\{
    Exception\BadMethodCallException,
    Exception\JsonException,
    Exception\UnexpectedValueException,
    Json,
    Options,
    String\Inflector};
use Psr\Http\Message\UriInterface;
use Stringable;

use function array_any;
use function array_keys;
use function function_exists;
use function in_array;
use function is_null;
use function str_starts_with;
use function strtolower;

use const null;
use const true;

/**
 * Request
 *
 * @version 0.6.6
 */
class Request extends AbstractRequest implements Stringable {
    /**
     * Limit properties to $magicPropertiesAllowed
     *
     * @var bool
     */
    protected bool $allowAllProperties = true;

    /**
     * Accept header
     *
     * @var string
     */
    protected string $accept = '';

    /**
     * properties
     *
     * @var Options
     */
    private Options $properties;

    /**
     * Limit properties to these
     *
     * @var string[]
     */
    private array $magicPropertiesAllowed = ['method'];

    /**
     * strings to remove from property names
     */
    public static array $propertyClean = ['request_', 'http_'];

    /**
     * Response
     *
     * @var Response
     */
    private Response $response;

    /**
     * Attached Files
     */
    protected array $files;

    /**
     * Query Params
     *
     * @var Options
     */
    private Options $query;

    /**
     * Holds data from POST.
     *
     * @var Options
     */
    private Options $post;

    /**
     * magic method: __get
     *
     * @param string $property - property name
     *
     * @return mixed the value of $property
     *
     * @throws PropertyException
     */
    public function __get(string $property) {
        if (!$this->allowAllProperties && !in_array($property, $this->magicPropertiesAllowed, true)) throw new PropertyException($property, 10);

        // TODO: Temp only => to upgrade implementations
        if (str_starts_with($property, 'http')) throw new PropertyException($property, 20);

        return $this->properties->get($property);
    }

    /**
     * Constructor method.
     *
     * @param null|string|HttpMethod   $method              The HTTP method for the request.
     * @param null|string|UriInterface $uri                 The URI for the request.
     * @param array                    $headers             An array of headers for the request.
     * @param mixed                    $body                The body of the request. Can be null or any data type.
     * @param null|string              $version             The HTTP protocol version.
     * @param bool                     $allowAllProperties  Flag to allow all properties to be accessible.
     * @param null|Response            $response            Optional response object.
     * @param bool                     $importApacheHeaders Flag to import headers from Apache if available.
     *
     * @return void
     *
     * @throws RuntimeException|JsonException If an error occurs during request initialization.
     */
    public function __construct(
        null|string|HttpMethod   $method = null,
        null|string|UriInterface $uri = null,
        array                    $headers = [],
        mixed                    $body = null,
        ?string                  $version = null,
        bool                     $allowAllProperties = true,
        ?Response                $response = null,
        bool                     $importApacheHeaders = false
    ) {
        if ($importApacheHeaders) {
            foreach (function_exists('apache_request_headers') ? apache_request_headers() : [] as $header => $value) {
                if (!array_any($headers, function (string|array $v, string $k) use ($header) {
                    return strtolower($k) === strtolower($header);
                })) $headers[$header] = $value;
            }
        }

        parent::__construct($method, $uri, $headers, $body, $version);

        $this->allowAllProperties = ($allowAllProperties === true);
        if (!is_null($response)) $this->response = $response;
        $this->bootstrapSelf();
    }

    /**
     * magic method: __toString
     *
     * @since 0.6.3
     *
     * @return string uri
     */
    public function __toString(): string {
        return $this->getUriString();
    }

    /**
     * Creates an instance from the given URL and optional headers.
     *
     * @since 0.6.0
     *
     * @param string $url     The URL to use for creating the instance.
     * @param array  $headers An optional array of headers to include.
     *
     * @return static A new instance initialized with the provided URL and headers.
     *
     * @throws RuntimeException|JsonException If the provided URL is invalid.
     */
    public static function fromUrl(string $url, array $headers = []): static {
        return new static(uri: $url, headers: $headers);
    }

    /**
     * Initialises the object with a server and request data.
     * Populates properties based on the server environment and request method.
     *
     * @return void
     *
     * @throws JsonException if unable to process POST or Query parameters.
     */
    private function bootstrapSelf(): void {
        $data = [];
        foreach ($_SERVER as $key => $value) $data[$this->toCamelCase($key)] = $value;

        if ($this->allowAllProperties) $this->magicPropertiesAllowed = array_keys($data);

        $this->properties = new Options($data);
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') $this->getPost();
        $this->getQuery();
    }

    private function toCamelCase($string): string {
        $result = str_replace(static::$propertyClean, '', strtolower($string));

        return Inflector::camelise($result);
    }

    /**
     * Determines and returns the accepted content type based on the provided `ACCEPT` header.
     *
     * @return string The accepted content type, either 'application/json', 'application/xml', or 'text/html'.
     */
    public function getAccept(): string {
        $accept = explode(',', $this->accept);
        $type = 'text/html';
        if (in_array('application/json', $accept) || in_array('*/*', $accept)) $type = 'application/json';
        else if (in_array('application/xml', $accept)) $type = 'application/xml';
        return $type;
    }

    /**
     * Retrieves the current response object or initialises a new one if it doesn't exist.
     *
     * @param string|null $body    The response body. If null, a default response is created.
     * @param int         $status  The HTTP status code for the response. Defaults to 200.
     * @param array|null  $headers An array of headers to set for the response. If null, default headers are used.
     *
     * @return Response The response object.
     * @throws BadMethodCallException
     * @throws UnexpectedValueException
     */
    public function getResponse(?string $body = null, int $status = 200, ?array $headers = null): Response {
        if (!isset($this->response)) {
            $this->response = $body === null ? new Response() : new Response($body, $status, $headers ?? ['Content-Type' => $this->getAccept()]);
            $this->response->setRequest($this);
        } else if (!is_null($body)) $this->response->setBody($body);
        return $this->response;
    }

    /**
     * Retrieves a POST request parameter or returns all POST data wrapped in an Options object.
     *
     * @since 0.6.6 Checks $_POST and php://input for data
     *
     * @param string|null $param   Name of the POST parameter to retrieve. If null, returns all POST data.
     * @param string|null $default Default value to return if the specified POST parameter is not found. Ignored if $param is null.
     *
     * @return Options An Options object containing the requested POST parameter or all POST data.
     *
     * @throws JsonException If the POST request body cannot be decoded when it contains JSON.
     */
    public function getPost(?string $param = null, ?string $default = null): Options {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (!isset($this->post)) $this->post = new Options((count($_POST) > 0 ? $_POST : Json::decode(file_get_contents('php://input'))) ?? []);

            if (!is_null($param)) return $this->post->get($param, $default);
            return $this->post;
        }
        return new Options([]);
    }

    /**
     * Retrieves a query parameter value or the complete query options.
     *
     * @param string|null $param   The name of the query parameter to retrieve. If null, the complete query options are returned.
     * @param string|null $default The default value to return if the specified parameter is not found.
     *
     * @return mixed The value of the specified query parameter, the complete query options, or the default value if the parameter is not found.
     *
     * @throws JsonException If the query object could not be initialized.
     */
    public function getQuery(?string $param = null, ?string $default = null): mixed {
        if (!isset($this->query)) $this->query = new Options($_GET);

        if (!is_null($param)) return $this->query->get($param, $default);
        return $this->query;
    }

    /**
     * Constructs a query string from the query parameters.
     *
     * @return string The constructed query string.
     *
     * @throws RuntimeException|JsonException If the query parameters cannot be converted to an array.
     */
    public function buildQuery(): string {
        return http_build_query($this->getQuery()->toArray());
    }

    /**
     * Retrieves the list of uploaded files.
     *
     * @return array An associative array of uploaded files.
     */
    public function getFiles(): array {
        if (!isset($this->files)) $this->files = $_FILES;
        return $this->files;
    }

    /**
     * Retrieves the URI as a string.
     *
     * @since 0.6.5
     *
     * @return string The URI string representation.
     *
     * @throws \RuntimeException If the URI cannot be retrieved or cast to a string.
     */
    public function getUriString(): string {
        return (string)$this->getUri();
    }
}
