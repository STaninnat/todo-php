<?php

/**
 * --------------------------------------------------------------------------
 * Conditional bootstrap for application or integration testing
 * --------------------------------------------------------------------------
 *
 * This script determines which bootstrap file should be loaded based on
 * the current application environment.
 *
 * - When running **integration tests**, we need to prepare a special setup
 *   (for example, database containers, mock services, or environment overrides)
 *   defined inside `bootstrap.integration.php`.
 *
 * - In all other cases (e.g., development, production, or unit tests),
 *   we only need to autoload classes from Composer via `vendor/autoload.php`.
 *
 * This approach ensures that test environments are fully isolated while
 * keeping the main application bootstrap lightweight and environment-aware.
 */

if (getenv('APP_ENV') === 'testing_integration') {
    /**
     * Integration testing environment
     *
     * Load the integration bootstrap file, which sets up:
     * - Environment variables from `.env.test`
     * - Test database initialization
     * - Any mock or service overrides required for testing
     */
    require __DIR__ . '/bootstrap.integration.php';
} else {
    /**
     * Non-testing environment
     *
     * Load the Composer autoloader only.
     * This provides automatic class loading for the application and dependencies.
     */
    require __DIR__ . '/../vendor/autoload.php';
}
