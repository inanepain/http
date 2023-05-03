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
