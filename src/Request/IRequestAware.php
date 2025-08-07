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
 * @version $version
 */

declare(strict_types=1);

namespace Inane\Http\Request;

use Psr\Http\Message\RequestInterface;

/**
 * IRequestAware
 *
 * @deprecated use Psr\Http\Message\RequestInterface
 *
 * @version 0.5.0
 *
 * @package Inane\Http
 */
interface IRequestAware {
    /**
     * set: request
     *
     * @param RequestInterface $request request
     * @return mixed
     */
    public function setRequest(RequestInterface $request);
}
