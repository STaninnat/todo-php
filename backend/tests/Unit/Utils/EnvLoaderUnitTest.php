<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use App\Utils\EnvLoader;
use PHPUnit\Framework\TestCase;

/**
 * Class EnvLoaderUnitTest
 *
 * Unit tests for the EnvLoader class.
 *
 * Covers:
 * - Loading .env files correctly.
 * - Loading .env.test files correctly.
 * - Handling missing files gracefully.
 *
 * @package Tests\Unit\Utils
 */
class EnvLoaderUnitTest extends TestCase
{
    private string $tempDir;

    /**
     * Set up test environment.
     *
     * Creates a temporary directory for test environment files.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Create a temporary directory for test env files
        $this->tempDir = sys_get_temp_dir() . '/env_loader_test_' . uniqid();
        mkdir($this->tempDir);
    }

    /**
     * Clean up test environment.
     *
     * Removes temporary files and directory created during tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Clean up temporary files (including hidden ones like .env)
        $files = glob("$this->tempDir/{,.}*", GLOB_BRACE);

        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        rmdir($this->tempDir);
    }

    /**
     * Test that EnvLoader::load() correctly loads variables from a .env file.
     *
     * @return void
     */
    public function testLoadLoadsDotEnvFile(): void
    {
        $envFile = $this->tempDir . '/.env';
        file_put_contents($envFile, 'TEST_VAR=loaded_from_env');

        EnvLoader::load($this->tempDir);

        $this->assertEquals('loaded_from_env', $_ENV['TEST_VAR'] ?? getenv('TEST_VAR'));

        // Cleanup env var to avoid side effects
        unset($_ENV['TEST_VAR']);
        putenv('TEST_VAR');
    }

    /**
     * Test that EnvLoader::loadTest() correctly loads variables from a .env.test file.
     *
     * @return void
     */
    public function testLoadTestLoadsDotEnvTestFile(): void
    {
        $envFile = $this->tempDir . '/.env.test';
        file_put_contents($envFile, 'TEST_VAR_TEST=loaded_from_env_test');

        EnvLoader::loadTest($this->tempDir);

        $this->assertEquals('loaded_from_env_test', $_ENV['TEST_VAR_TEST'] ?? getenv('TEST_VAR_TEST'));

        // Cleanup env var
        unset($_ENV['TEST_VAR_TEST']);
        putenv('TEST_VAR_TEST');
    }

    /**
     * Test that EnvLoader::load() does nothing if the .env file is missing.
     *
     * @return void
     */
    public function testLoadDoesNothingIfFileMissing(): void
    {
        // Ensure variable is unset
        unset($_ENV['NON_EXISTENT']);
        putenv('NON_EXISTENT');

        EnvLoader::load($this->tempDir); // Directory is empty

        $this->assertArrayNotHasKey('NON_EXISTENT', $_ENV);
    }
}
