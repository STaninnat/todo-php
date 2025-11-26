<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Interface ClockInterface
 *
 * Provides abstraction for retrieving current time-related values.
 *
 * @package App\Utils
 */
interface ClockInterface
{
    /**
     * Get the current timestamp.
     *
     * Example: 1694515200
     *
     * @return int Unix timestamp representing the current time.
     */
    public function now(): int;

    /**
     * Get the current date in string format.
     *
     * Example: "2025-09-12"
     *
     * @return string Current date as a formatted string.
     */
    public function today(): string;
}
