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

namespace Inane\Http\Exception;

use Exception;

/**
 * MethodException
 *
 * Parsed Method is Invalid
 *
 * @package Exception
 * @version 0.1.0
 */
class MethodException extends Exception {
    protected $message = 'Method exception';   // exception message
    protected $code = 100;                        // user defined exception code
    protected $file;                            // source filename of exception
    protected $line;                            // source line of exception

    // Redefine the exception so message isn't optional
    public function __construct(?string $message = null, $code = 0, Exception $previous = null) {
        $message = $this->message . ($message ? ': ' . $message : '');
        $code = $this->code + $code;

        $debugBacktrace = array_pop(debug_backtrace(0, 3));
        $this->file = $debugBacktrace['file'];
        $this->line = $debugBacktrace['line'];

        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    /**
     * magic method: __toString
     *
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ":\n [{$this->code}]: {$this->message}";
    }
}
