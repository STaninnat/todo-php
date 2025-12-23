<?php

declare(strict_types=1);

/**
 * Waits for the database service to become available.
 *
 * Tries to open a TCP connection to the given host and port repeatedly until
 * successful or the retry limit is reached. Commonly used in integration tests
 * or container setups to ensure the DB is ready before continuing.
 *
 * @param string $host         Database host or IP address
 * @param int    $port         Database port
 * @param int    $retries      Number of connection attempts (default: 10)
 * @param int    $delaySeconds Delay between attempts in seconds (default: 2)
 *
 * @throws RuntimeException If the database is still unreachable after all retries
 *
 * Example:
 * ```php
 * waitForDatabase('db_test', 3306, 20, 2);
 * ```
 */
function waitForDatabase(string $host, int $port, int $retries = 10, int $delaySeconds = 2): void
{
    $connected = false;

    // Attempt to connect multiple times before giving up
    for ($i = 0; $i < $retries; $i++) {
        // Try opening a socket connection to the specified host and port
        $fp = @fsockopen($host, $port);

        if ($fp) {
            // Connection successful -> close socket and stop retry loop
            fclose($fp);
            $connected = true;
            break;
        }
        // fwrite(STDERR, "Waiting for database to be ready... (attempt " . ($i + 1) . ")\n");

        // Pause before the next retry
        sleep($delaySeconds);
    }

    // If connection never succeeded, throw an exception to stop the process
    if (!$connected) {
        throw new RuntimeException("Database not ready after {$retries} attempts.");
    }
}
