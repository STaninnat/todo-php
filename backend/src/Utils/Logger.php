<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Class Logger
 *
 * A simple file-based logger with log rotation.
 *
 * Features:
 * - Writes logs into daily files (router-YYYY-MM-DD.log).
 * - Supports multiple log levels: INFO, WARNING, ERROR.
 * - Auto-creates log directory if missing.
 * - Cleans up old logs older than $maxDays.
 * - Can toggle debug mode to enable/disable logging.
 *
 * @package App\Utils
 */
class Logger
{
    /** @var bool Whether logging is enabled */
    private bool $debug;

    /** @var FileSystemInterface File system handler */
    private FileSystemInterface $fs;

    /** @var ClockInterface Clock provider for time and date */
    private ClockInterface $clock;

    /**
     * Logger constructor.
     *
     * @param FileSystemInterface $fs        File system handler (default: NativeFileSystem).
     * @param ClockInterface      $clock     Clock provider (default: SystemClock).
     * @param bool                $debug     Enable or disable logging.
     */
    public function __construct(
        FileSystemInterface $fs = new NativeFileSystem(),
        ClockInterface $clock = new SystemClock(),
        bool $debug = true
    ) {
        $this->fs = $fs;
        $this->clock = $clock;
        $this->debug = $debug;
    }

    /**
     * Write a log entry.
     *
     * @param string $level   Log level (e.g., INFO, WARNING, ERROR).
     * @param string $message Log message.
     *
     * @return void
     */
    private function write(string $level, string $message): void
    {
        // Always log ERROR, WARNING, FATAL
        // Only log other levels (INFO, etc.) if debug mode is enabled
        if (!$this->debug && !in_array($level, ['ERROR', 'WARNING'], true)) {
            return;
        }

        // Format log entry with timestamp and level
        $time = date('Y-m-d H:i:s', $this->clock->now());
        $formattedMessage = "[$time][$level] $message\n";

        // Determine stream based on level
        $stream = ($level === 'ERROR' || $level === 'WARNING') ? 'php://stderr' : 'php://stdout';

        $this->fs->write($stream, $formattedMessage, true);
    }

    /**
     * Log an info message.
     *
     * @param string $message Message to log.
     *
     * @return void
     */
    public function info(string $message): void
    {
        $this->write('INFO', $message);
    }

    /**
     * Log a warning message.
     *
     * @param string $message Message to log.
     *
     * @return void
     */
    public function warning(string $message): void
    {
        $this->write('WARNING', $message);
    }

    /**
     * Log an error message.
     *
     * @param string $message Message to log.
     *
     * @return void
     */
    public function error(string $message): void
    {
        $this->write('ERROR', $message);
    }

    /**
     * Enable or disable logging.
     *
     * @param bool $debug Whether logging should be enabled.
     *
     * @return void
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }
}
