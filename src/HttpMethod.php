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

namespace Inane\Http;

/**
 * HttpMethod
 *
 * @version 0.9.0
 *
 * @package Inane\Http
 */
enum HttpMethod: string {
    case Copy = 'COPY';
    case Delete = 'DELETE';
    case Get = 'GET';
    case Link = 'LINK';
    case Lock = 'LOCK';
    case Options = 'OPTIONS';
    case Patch = 'PATCH';
    case Post = 'POST';
    case PropFind = 'PROPFIND';
    case Purge = 'PURGE';
    case Put = 'PUT';
    case Unlink = 'UNLINK';
    case Unlock = 'UNLOCK';
    case View = 'VIEW';
}
