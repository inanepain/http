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

namespace Inane\Http\Request;

/**
 * iRequest
 *
 * @deprecated Use Psr\Http\Message\RequestInterface
 *
 * @package Http
 *
 * @version 0.5.0
 */
interface IRequest {
    public function getBody();
    public function getResponse();
}
