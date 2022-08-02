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

use Inane\Http\Message as HttpMessage;
use Psr\Http\Message\MessageInterface;

/**
 * AbstractMessage
 *
 * @version 0.6.3
 *
 * @deprecated 0.6.3
 *
 * @package Http
 */
class Message extends HttpMessage implements MessageInterface {}
