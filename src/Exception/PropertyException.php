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

namespace Inane\Http\Exception;

use Inane\Stdlib\Exception\Exception;

/**
 * PropertyException
 *
 * Adds Getters / Setters via magic get / set methods
 *
 * @package Inane\Http\Exception
 * @version 0.1.0
 */
class PropertyException extends Exception {
    protected $message = 'Property Invalid: `magic_property_name`'; // exception message
    protected $code = 200;                                          // user defined exception code

    /**
     * __construct
     *
     * @param null|string $message
     * @param int $code
     * @param Exception|null $previous
     * @return void
     */
    public function __construct(?string $message = null, $code = 0, Exception $previous = null) {
        if ($previous === null) $this->message = str_replace('magic_property_name', $message, $this->message);
        if ($code >= 10 && $code <= 19) $this->message = str_replace('Invalid', 'Denied', $this->message);

        parent::__construct($this->message, $code, $previous);
    }
}
