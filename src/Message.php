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

use Inane\Http\Psr\Message as PsrMessage;
use Psr\Http\Message\MessageInterface;

/**
 * Message
 *
 * @version 0.6.0
 *
 * @package Http
 */
class Message extends PsrMessage implements MessageInterface {
}
