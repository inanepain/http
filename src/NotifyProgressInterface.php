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
 * @author   Philip Michael Raab<philip@cathedral.co.za>
 * @package  inanepain\http
 * @category http
 *
 * @license  UNLICENSE
 * @license  https://unlicense.org/UNLICENSE UNLICENSE
 *
 * _version_ $version
 */

declare(strict_types = 1);

namespace Inane\Http;

/**
 * Defines an interface for notifying progress updates.
 */
interface NotifyProgressInterface {
    /**
     * Tracks and reports the progress of a download process.
     *
     * @param int   $download_total The total size of the download, in bytes.
     * @param int   $downloaded     The amount of data downloaded so far, in bytes.
     * @param float $percent        The percentage of the download completed.
     *
     * @return void
     */
    public function progress(int $download_total, int $downloaded, float $percent): void;
}
