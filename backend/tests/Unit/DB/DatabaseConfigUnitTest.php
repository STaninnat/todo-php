<?php

declare(strict_types=1);

namespace Tests\Unit\DB;

use App\DB\DatabaseConfig;
use PHPUnit\Framework\TestCase;

/**
 * Class DatabaseConfigUnitTest
 *
 * Unit tests for the DatabaseConfig class.
 *
 * This test suite verifies:
 * - Retrieval of environment variables with prefix logic
 * - Fallback mechanism to standard variable names
 * - Handling of missing variables
 *
 * @package Tests\Unit\DB
 */
class DatabaseConfigUnitTest extends TestCase
{
    /**
     * Clean up environment after each test.
     * 
     * @return void
     */
    protected function tearDown(): void
    {
        // Clear env vars used in tests
        putenv('DB_SOURCE');
        putenv('DB_HOST');
        putenv('LC_DB_HOST');
        putenv('AIVEN_DB_HOST');
        unset($_ENV['DB_SOURCE'], $_ENV['DB_HOST'], $_ENV['LC_DB_HOST'], $_ENV['AIVEN_DB_HOST']);
    }

    /**
     * Test: get() should return a basic non-prefixed variable.
     * 
     * @return void
     */
    public function testGetBasicVariable(): void
    {
        putenv('DB_HOST=localhost');
        $config = new DatabaseConfig();
        $this->assertSame('localhost', $config->get('DB_HOST'));
    }

    /**
     * Test: get() should return variable with 'local' source prefix (LC_)
     * when DB_SOURCE is set to 'local'.
     * 
     * @return void
     */
    public function testGetLocalPrefixedVariable(): void
    {
        putenv('DB_SOURCE=local');
        putenv('LC_DB_HOST=127.0.0.1');
        putenv('DB_HOST=localhost'); // Should be ignored in favor of prefixed

        $config = new DatabaseConfig();
        $this->assertSame('127.0.0.1', $config->get('DB_HOST'));
    }

    /**
     * Test: get() should return variable with 'cloud' source prefix (AIVEN_)
     * when DB_SOURCE is set to 'cloud'.
     * 
     * @return void
     */
    public function testGetCloudPrefixedVariable(): void
    {
        putenv('DB_SOURCE=cloud');
        putenv('AIVEN_DB_HOST=cloud-db-host');
        putenv('DB_HOST=localhost');

        $config = new DatabaseConfig();
        $this->assertSame('cloud-db-host', $config->get('DB_HOST'));
    }

    /**
     * Test: get() should fallback to standard variable name
     * when source is set but prefixed variable is missing.
     * 
     * @return void
     */
    public function testFallbackToStandardVariable(): void
    {
        putenv('DB_SOURCE=local');
        // LC_DB_HOST is NOT set
        putenv('DB_HOST=fallback-host');

        $config = new DatabaseConfig();
        $this->assertSame('fallback-host', $config->get('DB_HOST'));
    }

    /**
     * Test: get() should return null for non-existent variables.
     * 
     * @return void
     */
    public function testGetMissingVariableReturnsNull(): void
    {
        $config = new DatabaseConfig();
        $this->assertNull($config->get('NON_EXISTENT_VAR'));
    }
}
