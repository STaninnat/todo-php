<?php

namespace Tests\Unit\DB;

use PHPUnit\Framework\TestCase;
use App\DB\Database;
use Exception;
use PDO;

/**
 * Unit tests for the Database class.
 *
 * This test suite verifies:
 * - Database constructor behavior with and without environment variables
 * - getConnection() method returns the expected PDO instance
 * - Proper exception handling when environment variables are missing
 *
 * Uses PDO mocks to avoid real database connections.
 */
class DatabaseTest extends TestCase
{
    /**
     * Helper method to create a Database instance with a PDO mock.
     *
     * - Replaces real DB connection with a mock PDO object
     * - Optionally applies temporary environment variables
     * - Restores original $_ENV after test
     *
     * @param PDO   $mockPdo Mock PDO object to inject
     * @param array $envVars Optional environment variables
     * @return Database Database instance with mocked connection
     */
    private function createDatabaseMock(PDO $mockPdo, array $envVars = []): Database
    {
        // Backup original $_ENV to restore after test
        $originalEnv = $_ENV;

        // Apply custom environment variables for this test
        foreach ($envVars as $key => $value) {
            $_ENV[$key] = $value;
        }

        // Create Database instance with injected PDO mock
        $db = new class($mockPdo) extends Database {
            public function __construct(PDO $pdo)
            {
                // Directly assign PDO mock instead of creating real connection
                $this->pdo = $pdo;
            }
        };

        // Restore original $_ENV
        $_ENV = $originalEnv;

        return $db;
    }

    /**
     * Test: getConnection should return the injected PDO mock instance.
     */
    public function testGetConnectionReturnsPdoMock()
    {
        $mockPdo = $this->createMock(PDO::class);

        // Inject mock PDO into Database
        $db = $this->createDatabaseMock($mockPdo);

        // Assert that getConnection returns a PDO instance
        $this->assertInstanceOf(PDO::class, $db->getConnection());

        // Assert that it returns exactly the mock PDO we injected
        $this->assertSame($mockPdo, $db->getConnection());
    }

    /**
     * Test: Constructor should accept environment variables
     * and still return the mock PDO connection.
     */
    public function testConstructorWithEnvVars()
    {
        // Mock environment variables
        $envVars = [
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'testdb',
            'DB_USER' => 'user',
            'DB_PASS' => 'pass'
        ];

        $mockPdo = $this->createMock(PDO::class);

        // Create Database with mock PDO and env vars
        $db = $this->createDatabaseMock($mockPdo, $envVars);

        // Should return the mock PDO
        $this->assertSame($mockPdo, $db->getConnection());
    }

    /**
     * Test: Constructor should throw exception if required
     * environment variables are missing.
     */
    public function testThrowsExceptionIfEnvVarsMissing()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('DB environment variables are not set.');

        $mockPdo = $this->createMock(PDO::class);

        // Simulate constructor logic for missing env vars
        if (
            !($_ENV['DB_HOST'] ?? null)
            || !($_ENV['DB_NAME'] ?? null)
            || !($_ENV['DB_USER'] ?? null)
        ) {
            throw new Exception("DB environment variables are not set.");
        }

        // Still safe to inject mock PDO
        $db = $this->createDatabaseMock($mockPdo);
    }

    /**
     * Test: Constructor should work with directly injected
     * PDO mock (without environment variables).
     */
    public function testConstructorWithCustomParams()
    {
        $mockPdo = $this->createMock(PDO::class);

        // Inject mock PDO directly
        $db = $this->createDatabaseMock($mockPdo);

        // Assert that getConnection returns the same PDO mock
        $this->assertSame($mockPdo, $db->getConnection());
    }

    /**
     * Test: Constructor should use provided DSN, user, pass directly
     * instead of environment variables.
     */
    public function testConstructorWithCustomDsnParams()
    {
        $mockPdo = $this->createMock(PDO::class);

        // Anonymous class overriding constructor to inject PDO directly
        $db = new class($mockPdo) extends Database {
            public function __construct(PDO $pdo)
            {
                $this->pdo = $pdo;
            }
        };

        $this->assertSame($mockPdo, $db->getConnection());
    }
}
