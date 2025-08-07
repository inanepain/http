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

/**
 * iRequest
 *
 * @deprecated Use Psr\Http\Message\RequestInterface
 *
 * @package Inane\Http
 *
 * @version 0.5.0
 */
interface IRequest {
    public function getBody();
    public function getResponse();
}
