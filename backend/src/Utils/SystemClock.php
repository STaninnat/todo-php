<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Class SystemClock
 *
 * Native implementation of ClockInterface using PHP built-in time functions.
 * Provides current timestamp and today's date.
 *
 * @package App\Utils
 */
class SystemClock implements ClockInterface
{
    /**
     * Get the current Unix timestamp.
     *
     * @return int Current timestamp (seconds since Unix epoch).
     */
    public function now(): int
    {
        // Return current system time as Unix timestamp
        return time();
    }

    /**
     * Get today's date in "YYYY-MM-DD" format.
     *
     * @return string Current date formatted as "Y-m-d".
     */
    public function today(): string
    {
        // Return current date as string
        return date('Y-m-d');
    }
}
