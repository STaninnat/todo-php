<?php

declare(strict_types=1);

namespace App\Utils;

use Dotenv\Dotenv;

/**
 * Class EnvLoader
 *
 * Centralized loader for environment variables.
 * Ensures consistent loading logic across Web, CLI, and Test environments.
 */
class EnvLoader
{
    /**
     * Load environment variables from the specified directory.
     *
     * @param string $path The directory containing the .env file.
     *
     * @return void
     */
    public static function load(string $path): void
    {
        if (file_exists($path . '/.env')) {
            $dotenv = Dotenv::createImmutable($path);
            $dotenv->safeLoad();
        }
    }

    /**
     * Load test environment variables.
     *
     * @param string $path The directory containing the .env.test file.
     *
     * @return void
     */
    public static function loadTest(string $path): void
    {
        if (file_exists($path . '/.env.test')) {
            $dotenv = Dotenv::createImmutable($path, '.env.test');
            $dotenv->safeLoad();
        }
    }
}
