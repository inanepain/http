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

use Inane\Http\Exception\PropertyException;
use Inane\Http\Request\AbstractRequest;
use Inane\Inflection\Infector;
use Inane\Stdlib\Options;

use function array_keys;
use function class_exists;
use function in_array;
use function is_null;
use function str_starts_with;
use const false;
use const null;
use const true;

/**
 * Request
 *
 * @version 0.6.1
 *
 * @package Http
 */
class Request extends AbstractRequest {
    public const METHOD_COPY = 'COPY';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_GET = 'GET';
    public const METHOD_LINK = 'LINK';
    public const METHOD_LOCK = 'LOCK';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_POST = 'POST';
    public const METHOD_PROPFIND = 'PROPFIND';
    public const METHOD_PURGE = 'PURGE';
    public const METHOD_PUT = 'PUT';
    public const METHOD_UNLINK = 'UNLINK';
    public const METHOD_UNLOCK = 'UNLOCK';
    public const METHOD_VIEW = 'VIEW';

    protected bool $allowAllProperties = true;

    /**
     * properties
     *
     * @var Options
     */
    private Options $properties;

    protected array $magicPropertiesAllowed = ['method'];

    /**
     * strings to remove from property names
     */
    static array $propertyClean = ['request_', 'http_'];

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
     * Post data
     *
     * @var \Inane\Config\Options
     */
    protected Options $post;

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

    /**
     * Response
     * @param bool $allowAllProperties
     * @return void
     */
    public function __construct(bool $allowAllProperties = true, ?Response $response = null) {
        $headers = apache_request_headers();
        parent::__construct(null, null, $headers);

        $this->allowAllProperties = ($allowAllProperties === true);
        if (!is_null($response)) $this->response = $response;
        $this->bootstrapSelf();
    }

    /**
     * Create a Request from $url
     *
     * @param string $url target url
     *
     * @since 0.6.0
     *
     * @return static the Request
     */
    public static function fromUrl(string $url): static {
        $r = new static();
        $r = $r->withUri(new Uri($url));
        return $r;
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

        return Infector::camelise($result);
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
     * @param null|string $param get specific param
     * @param null|string $default
     *
     * @return \Inane\Config\Options
     */
    public function getPost(?string $param = null, ?string $default = null): Options {
        if (!isset($this->post)) $this->post = new Options($_POST ?? []);

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
}
