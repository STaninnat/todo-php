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
    /** @var string Directory where log files are stored */
    private string $directory;

    /** @var bool Whether logging is enabled */
    private bool $debug;

    /** @var string Current log file name */
    private string $currentFile;

    /** @var int Maximum number of days to keep logs */
    private int $maxDays;

    /** @var FileSystemInterface File system handler */
    private FileSystemInterface $fs;

    /** @var ClockInterface Clock provider for time and date */
    private ClockInterface $clock;

    /**
     * Logger constructor.
     *
     * @param string              $directory Directory for storing log files.
     * @param FileSystemInterface $fs        File system handler (default: NativeFileSystem).
     * @param ClockInterface      $clock     Clock provider (default: SystemClock).
     * @param bool                $debug     Enable or disable logging.
     * @param int                 $maxDays   Days to keep logs before deletion.
     */
    public function __construct(
        string $directory,
        FileSystemInterface $fs = new NativeFileSystem(),
        ClockInterface $clock = new SystemClock(),
        bool $debug = true,
        int $maxDays = 30
    ) {
        $this->directory = rtrim($directory, '/');
        $this->fs = $fs;
        $this->clock = $clock;
        $this->debug = $debug;
        $this->maxDays = $maxDays;

        // Ensure log directory exists
        $this->fs->ensureDir($this->directory);

        // Set the current log file for today
        $this->updateCurrentFile();

        // Remove old log files beyond $maxDays
        $this->cleanupOldLogs();
    }

    /**
     * Update the current log file based on today's date.
     *
     * @return void
     */
    private function updateCurrentFile(): void
    {
        $date = $this->clock->today();
        $this->currentFile = $this->directory . "/router-$date.log";
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
        if (!$this->debug) {
            return; // Skip logging if debug mode is disabled
        }

        // Always refresh current log file and cleanup old logs
        $this->updateCurrentFile();
        $this->cleanupOldLogs();

        // Format log entry with timestamp and level
        $time = date('Y-m-d H:i:s', $this->clock->now());
        $this->fs->write($this->currentFile, "[$time][$level] $message\n", true);
    }

    /**
     * Remove log files older than $maxDays.
     *
     * @return void
     */
    private function cleanupOldLogs(): void
    {
        $files = $this->fs->listFiles($this->directory . '/router-*.log');
        $now = $this->clock->now();

        foreach ($files as $file) {
            // Extract date part from file name: router-YYYY-MM-DD.log
            $fileDateStr = basename($file, '.log');
            $datePart = substr($fileDateStr, 7);

            // Convert to timestamp
            $fileTime = strtotime($datePart);
            if ($fileTime === false) {
                continue; // Skip invalid filenames
            }

            // If older than $maxDays, delete file
            if (($now - $fileTime) / 86400 > $this->maxDays) {
                $this->fs->delete($file);
            }
        }
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

    /**
     * Set maximum number of days to keep log files.
     *
     * @param int $days Number of days.
     *
     * @return void
     */
    public function setMaxDays(int $days): void
    {
        $this->maxDays = $days;
    }
}
