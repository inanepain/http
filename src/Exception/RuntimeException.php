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

namespace Inane\Http\Exception;

use Inane\Stdlib\Exception\RuntimeException as InaneRuntimeException;

/**
 * RuntimeException
 *
 * @package Inane\Http\Exception
 * @version 0.3.0
 */
class RuntimeException extends InaneRuntimeException {
    protected $code = 700;
}
