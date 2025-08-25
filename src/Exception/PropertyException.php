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
 * _version_ $version
 */

namespace Inane\Http\Exception;

use Inane\Stdlib\Exception\Exception;

/**
 * PropertyException
 *
 * Adds Getters / Setters via magic get / set methods
 * 
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
