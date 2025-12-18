<?php

declare(strict_types=1);

namespace App\DB;

use Dotenv\Dotenv;

/**
 * Class DatabaseConfig
 * 
 * Handles the loading and retrieval of database configuration from environment variables.
 * Supports different configuration sources (local, cloud) via prefixes.
 * 
 * @package App\DB
 */
final class DatabaseConfig
{
    /**
     * DatabaseConfig constructor.
     * 
     * Automatically loads environment variables from .env file if running outside
     * of a containerized environment (indicated by missing DB_HOST).
     */
    public function __construct()
    {
        // Load .env only if running outside Docker (no DB_HOST env set)
        if (!getenv('DB_HOST') && file_exists(dirname(__DIR__) . '/.env')) {
            $envFile = getenv('APP_ENV') === 'testing' ? '.env.test' : '.env';
            Dotenv::createImmutable(dirname(__DIR__), $envFile)->safeLoad();
        }
    }

    /**
     * Retrieve a configuration value by name.
     * 
     * Checks for source-specific prefixes (e.g., LC_, AIVEN_) based on DB_SOURCE.
     * Falls back to the base name if the prefixed version is not found.
     * 
     * @param string $name The name of the environment variable (e.g., 'DB_HOST')
     * @return string|null The value of the variable, or null if not found
     */
    public function get(string $name): ?string
    {
        // Determine the variable prefix based on the DB_SOURCE environment variable
        $source = getenv('DB_SOURCE') ?: $_ENV['DB_SOURCE'] ?? null;

        $prefix = match ($source) {
            'local' => 'LC_',
            'aiven', 'cloud' => 'AIVEN_',
            default => '',
        };

        // Try to find the variable with the prefix, then fall back to the original name
        foreach ([$prefix . $name, $name] as $key) {
            $val = getenv($key) ?: $_ENV[$key] ?? null;
            if (is_string($val) && $val !== '') {
                return $val;
            }
        }

        return null;
    }
}
