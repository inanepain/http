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

use Inane\Http\Exception\PropertyException;
use Inane\Http\Request\AbstractRequest;
use Stringable;

use function array_keys;
use function function_exists;
use function in_array;
use function is_null;
use function str_starts_with;
use function array_any;
use function strtolower;
use const null;
use const true;

use Inane\Stdlib\{Json, String\Inflector, Options};
use Psr\Http\Message\UriInterface;

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
    static array $propertyClean = ['request_', 'http_'];

    /**
     * Response
     *
     * @var \Inane\Http\Response
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
     * Post data
     *
     * @var \Inane\Stdlib\Options
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
        if (!$this->allowAllProperties && !in_array($property, $this->magicPropertiesAllowed)) throw new PropertyException($property, 10);

        // TODO: Temp only => to upgrade implementations
        if (str_starts_with($property, 'http')) throw new PropertyException($property, 20);

        return $this->properties->offsetGet($property, null);
    }


    // /**
    //  * Constructs a new Request instance.
    //  *
    //  * @param bool $allowAllProperties Determines if all properties are allowed.
    //  * @param Response|null $response Optional response object associated with the request.
    //  * @param array|null $headers Optional array of headers to include in the request.
    //  */
    // public function __construct(bool $allowAllProperties = true, ?Response $response = null, ?array $headers = null) {

    /**
     * Request
     *
     * @param null|string|HttpMethod               $method              HTTP method
     * @param null|string|UriInterface             $uri                 URI
     * @param array<string, string|string[]>       $headers             Request headers
     * @param string|resource|StreamInterface|null $body                Request body
     * @param string|null                          $version             Protocol version
     * @param bool                                 $allowAllProperties  Determines if all properties are allowed.
     * @param Response|null                        $response            Optional response object associated with the request.
     * @param bool                                 $importApacheHeaders Import headers from `apache_request_headers`.
     */
    public function __construct(
        null|string|HttpMethod $method = null,
        null|string|UriInterface $uri = null,
        array $headers = [],
        $body = null,
        ?string $version = null,
        bool $allowAllProperties = true,
        ?Response $response = null,
        bool $importApacheHeaders = false
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
     * Create a Request from $url
     *
     * @param string $url     target url
     * @param array  $headers Optional array of headers to include in the request.
     *
     * @since 0.6.0
     *
     * @return static the Request
     */
    public static function fromUrl(string $url, array $headers = []): static {
        return new static(uri: $url, headers: $headers);
    }

    /**
     * setup request
     *
     * @return void
     */
    private function bootstrapSelf(): void {
        $data = [];
        foreach ($_SERVER as $key => $value) $data[$this->toCamelCase($key)] = $value;

        if ($this->allowAllProperties) $this->magicPropertiesAllowed = array_keys($data);

        $this->properties = new Options($data);
        $this->getPost();
        $this->getQuery();
    }

    private function toCamelCase($string) {
        $result = str_replace(static::$propertyClean, '', strtolower($string));

        return Inflector::camelise($result);
    }

    /**
     * get accept
     *
     * @return string
     */
    public function getAccept(): string {
        $accept = explode(',', $this->accept);
        $type = 'text/html';
        if (in_array('application/json', $accept) || in_array('*/*', $accept)) $type = 'application/json';
        else if (in_array('application/xml', $accept)) $type = 'application/xml';
        return $type;
    }

    /**
     * Get a response based on this request
     *
     * @param string|null $body
     * @param int $status
     * @param array|null $headers
     *
     * @return Response
     */
    public function getResponse(?string $body = null, $status = 200, ?array $headers = null): Response {
        if (!isset($this->response)) {
            $this->response = $body == null ? new Response() : new Response($body, $status, $headers ?? ['Content-Type' => $this->getAccept()]);
            $this->response->setRequest($this);
        } else if (!is_null($body)) $this->response->setBody($body);
        return $this->response;
    }

    /**
     * Get POST data
     *
     * @since 0.6.6 Checkes $_POST and php://input for data
     *
     * @param null|string $param get specific param
     * @param null|string $default
     *
     * @return \Inane\Stdlib\Options
     */
    public function getPost(?string $param = null, ?string $default = null): Options {
        if (!isset($this->post)) $this->post = new Options((count($_POST) > 0 ? $_POST : Json::decode(file_get_contents('php://input'))) ?? []);

        if (!is_null($param)) return $this->post->get($param, $default);
        return $this->post;
    }

    /**
     * get: Query Params
     *
     * @param null|string $param get specific param
     * @param null|string $default
     *
     * @return mixed param/params
     */
    public function getQuery(?string $param = null, ?string $default = null): mixed {
        if (!isset($this->query)) $this->query = new Options($_GET);

        if (!is_null($param)) return $this->query->get($param, $default);
        return $this->query;
    }

    /**
     * get: query string with any modifications
     *
     * @return string query string
     */
    public function buildQuery(): string {
        return http_build_query($this->getQuery()->toArray());
    }

    /**
     * get: uploaded files, if any
     *
     * @return array files
     */
    public function getFiles(): array {
        if (!isset($this->files)) $this->files = $_FILES;
        return $this->files;
    }

    /**
     * get: uri as string
     *
     * @since 0.6.5
     *
     * @return string url
     */
    public function getUriString(): string {
        return (string)$this->getUri();
    }
}
