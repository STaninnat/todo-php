<?php

declare(strict_types=1);

namespace Tests;

use function Tests\Integration\waitForDatabase;

/**
 * Load environment variables from .env.test file if it exists.
 *
 * This allows the test environment to override default settings
 * without affecting production or development environments.
 */
$envFile = __DIR__ . '/../.env.test';

if (is_file($envFile)) {
    /** @var string[] $lines Lines read from the .env.test file */
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip empty lines or lines starting with '#' (comments)
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Split the line into key and value. If the value is missing, default to empty string.
        // Example: 'DB_HOST=localhost' => $key='DB_HOST', $value='localhost'
        [$key, $value] = explode('=', $line, 2) + [1 => ''];

        $key = (string)$key;
        $value = (string)$value;

        // Set the key-value pair in PHP's environment ($_ENV) and system environment
        // Using both ensures compatibility with code that reads either $_ENV or getenv()
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

/**
 * @var string $dbHost Database host for integration tests
 * @var int    $dbPort Database port for integration tests
 */
$dbHost = 'db_test'; // default test DB host
$dbPort = 3306;      // default MySQL port for testing

// Override defaults if environment variables are present
if (isset($_ENV['DB_HOST']) && is_string($_ENV['DB_HOST'])) {
    $dbHost = $_ENV['DB_HOST'];
}

if (isset($_ENV['DB_PORT']) && is_numeric($_ENV['DB_PORT'])) {
    $dbPort = (int)$_ENV['DB_PORT'];
}

/**
 * Wait for the database to be ready
 *
 * This function repeatedly attempts to connect to the database until:
 *   1. The database responds successfully, or
 *   2. The maximum number of retries is reached.
 *
 * Parameters:
 *   - $dbHost: Database host to connect to
 *   - $dbPort: Database port to connect to
 *   - 20      : Maximum number of connection attempts
 *   - 2       : Delay in seconds between each attempt
 *
 * This is useful for CI/CD pipelines where the database container
 * may take a few seconds to start before tests can safely run.
 */
waitForDatabase($dbHost, $dbPort, 20, 2);
