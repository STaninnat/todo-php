<?php

namespace Tests\Unit\Utils\Fakes;

use App\Utils\ClockInterface;

/**
 * Class FakeClock
 *
 * A fake clock implementation for unit testing.
 * Allows setting a fixed timestamp and advancing time manually.
 *
 * @package Tests\Unit\Utils\Fakes
 */
class FakeClock implements ClockInterface
{
    /** @var int Current timestamp used by the fake clock */
    private int $timestamp;

    /**
     * Constructor.
     *
     * @param int $timestamp Initial timestamp for the fake clock.
     */
    public function __construct(int $timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Return the current timestamp.
     *
     * @return int Current timestamp
     */
    public function now(): int
    {
        // Return the fixed timestamp
        return $this->timestamp;
    }

    /**
     * Return today's date based on the current timestamp.
     *
     * @return string Current date in "YYYY-MM-DD" format
     */
    public function today(): string
    {
        // Format timestamp as date string
        return date('Y-m-d', $this->timestamp);
    }

    /**
     * Advance the clock by a number of days.
     *
     * @param int $days Number of days to advance
     *
     * @return void
     */
    public function advanceDays(int $days): void
    {
        // Increase timestamp by given days (seconds)
        $this->timestamp += $days * 86400;
    }
}
