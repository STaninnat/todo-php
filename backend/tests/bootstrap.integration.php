<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Integration/bootstrap_db.php';

use App\Utils\EnvLoader;

EnvLoader::loadTest(__DIR__ . '/..');

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
    $dbPort = (int) $_ENV['DB_PORT'];
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
