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

namespace Inane\Http\Psr;

use Inane\Http\Stream as BaseStream;
use Psr\Http\Message\StreamInterface;

/**
 * Stream
 *
 * @version 0.5.1
 *
 * @deprecated 0.5.1
 *
 * @package Http
 */
class Stream extends BaseStream implements StreamInterface {
}
