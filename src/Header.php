<?php

/**
 * Header
 *
 * Inane Library
 *
 * $Id$
 * $Date$
 *
 * PHP version 8.5
 *
 * @author   Philip Michael Raab <philip@cathedral.co.za>
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

use Stringable;

use function array_key_exists;
use function array_merge;
use function array_unique;
use function implode;
use function is_array;
use function strtolower;

/**
 * Header
 *
 * inane-fw
 *
 * @version 0.1.0
 */
class Header implements Stringable {
    /**
     * Header formatting options.
     *
     * @var array<string, string>
     */
    protected array $options = [
        'delimiter' => ',',
    ];

    /**
     * Separator used when joining header values.
     *
     * @var string
     */
    protected string $delimiter {
        get => $this->options[__PROPERTY__];
    }

    /**
     * Normalised header name for lookups.
     *
     * @var string
     */
    public string $key {
        // Cache the normalised name on first access.
        get => $this->key ??= static::normalise($this->name);
    }

    /**
     * Stored header values.
     *
     * @var array<array-key, string>
     */
    protected array $value = [];

    /**
     * Initialise the header name, values and formatting options.
     *
     * @param string                         $name    Header name.
     * @param array<array-key, string>|string $value   Header values.
     * @param array<string, string>           $options Formatting options.
     *
     * @throws \TypeError If a recognised option value is not a string.
     */
    public function __construct(
        /**
         * Original header name used when rendering.
         *
         * @var string
         */
        protected(set) string $name,
        array|string          $value = [],
        array                 $options = [],
    ) {
        $this->setValue($value);
        $this->setOptions($options);
    }

    /**
     * Lowercase a string and trim surrounding whitespace.
     *
     * @param string $string String to normalise.
     *
     * @return string Normalised string.
     */
    public static function normalise(string $string): string {
        return strtolower($string) |> trim(...);
    }

    /**
     * Apply recognised formatting options.
     *
     * @param array<string, string> $options Formatting options.
     *
     * @return static This header.
     *
     * @throws \TypeError If a recognised option value is not a string.
     */
    protected function setOptions(array $options = []): static {
        // Only existing option names are accepted.
        foreach($this->options as $option => $value) {
            if (array_key_exists($option, $options)) $this->setOption($option, $options[$option]);
        }

        return $this;
    }

    /**
     * Update a recognised option using its normalised name.
     *
     * @param string $option Option name.
     * @param string $value  Option value.
     *
     * @return static This header.
     */
    protected function setOption(string $option, string $value): static {
        $key = static::normalise($option);

        if (array_key_exists($key, $this->options)) $this->options[$key] = $value;

        return $this;
    }

    /**
     * Replace stored values or merge additional unique values.
     *
     * @param string|array<array-key, string> $value   Header values.
     * @param bool                           $replace Whether to replace stored values.
     *
     * @return static This header.
     */
    public function setValue(string|array $value, bool $replace = false): static {
        if (!is_array($value)) $value = [$value];

        // Replacement preserves the supplied values; merging removes duplicates.
        if ($replace) $this->value = $value;
        else $this->value = array_unique(array_merge($this->value, $value));

        return $this;
    }

    /**
     * Retrieve the stored header values.
     *
     * @return array<array-key, string> Header values.
     */
    public function getValue(): array {
        return $this->value;
    }

    /**
     * Join the stored header values.
     *
     * @param string|null $delimiter Separator override, or null to use the configured separator.
     *
     * @return string Joined header values.
     */
    public function getLine(?string $delimiter = null): string {
        return implode($delimiter ?? $this->delimiter, $this->value);
    }

    /**
     * Render the header name and values.
     *
     * @param string|null $delimiter Separator override, or null to use the configured separator.
     *
     * @return string Complete header line.
     */
    public function getHeader(?string $delimiter = null): string {
        return $this->name . ': ' . $this->getLine($delimiter);
    }

    /**
     * Render the header when invoked as a callable.
     *
     * @param string|null $delimiter Separator override, or null to use the configured separator.
     *
     * @return string Complete header line.
     */
    public function __invoke(?string $delimiter = null): string {
        return $this->getHeader($delimiter);
    }

    /**
     * Render the header using the configured separator.
     *
     * @return string Complete header line.
     */
    public function __toString(): string {
        return $this->getHeader();
    }

    /**
     * Export the stored values keyed by the original header name.
     *
     * @return array<string, array<array-key, string>> Header name and values.
     */
    public function toArray(): array {
        return [$this->name => $this->value];
    }
}
