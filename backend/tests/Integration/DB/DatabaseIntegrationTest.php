<?php

declare(strict_types=1);

namespace Tests\Integration\DB;

use App\DB\Database;
use PHPUnit\Framework\TestCase;
use PDO;

require_once __DIR__ . '/../bootstrap_db.php';

/**
 * Class DatabaseIntegrationTest
 *
 * Integration tests for the Database class.
 * 
 * This test suite verifies:
 * - Actual database connection can be established
 * - Table creation, insertion, and query execution
 * - PDO attributes are configured correctly
 * - Environment variables are loaded correctly
 *
 * Uses a real database connection, so bootstrap_db.php must set up the environment.
 *
 * @package Tests\Integration\DB
 */
final class DatabaseIntegrationTest extends TestCase
{
    /**
     * @var PDO PDO instance for integration testing
     */
    private PDO $pdo;

    /**
     * Setup before each test.
     *
     * Loads environment variables, waits for database to be ready,
     * and establishes a PDO connection.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Get DB host and port from environment or defaults
        $dbHost = $this->getEnvString('DB_HOST', 'db_test');
        $dbPort = $this->getEnvInt('DB_PORT', 3306);

        // Wait until the database is ready to accept connections
        waitForDatabase($dbHost, $dbPort);

        // Create Database instance and get PDO connection
        $this->pdo = (new Database())->getConnection();
    }

    /**
     * Helper method to read environment variable as string.
     *
     * @param string $key Environment variable name
     * @param string $default Default value if not set
     *
     * @return string
     */
    private function getEnvString(string $key, string $default): string
    {
        $val = $_ENV[$key] ?? $default;

        // Convert scalar values to string
        if (is_string($val)) {
            return $val;
        }
        if (is_scalar($val)) {
            return (string)$val;
        }

        return $default;
    }

    /**
     * Helper method to read environment variable as integer.
     *
     * @param string $key Environment variable name
     * @param int $default Default value if not set or invalid
     *
     * @return int
     */
    private function getEnvInt(string $key, int $default): int
    {
        $val = $_ENV[$key] ?? $default;
        if (is_int($val)) {
            return $val;
        }
        if (is_string($val) && ctype_digit($val)) {
            return (int)$val;
        }

        return $default;
    }

    /**
     * Test that PDO connection is established.
     *
     * @return void
     */
    public function testDatabaseConnectionIsEstablished(): void
    {
        $this->assertInstanceOf(PDO::class, $this->pdo);
    }

    /**
     * Test creating a table, inserting data, and querying it.
     *
     * @return void
     */
    public function testCanCreateAndQueryTable(): void
    {
        // Ensure table does not exist before creating
        $this->pdo->exec('DROP TABLE IF EXISTS test_items');

        // Create table for testing
        $this->pdo->exec('CREATE TABLE test_items (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))');

        // Insert sample data
        $this->pdo->exec("INSERT INTO test_items (name) VALUES ('Item 1'), ('Item 2')");

        // Query table for count of rows
        $stmt = $this->pdo->query('SELECT COUNT(*) as count FROM test_items');
        if ($stmt === false) {
            $this->fail('Query failed');
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validate fetched row
        if (!is_array($row) || !isset($row['count']) || !is_numeric($row['count'])) {
            $this->fail('Fetch returned invalid count');
        }

        $this->assertSame(2, (int)$row['count']);
    }

    /**
     * Test that PDO attributes are correctly configured.
     *
     * @return void
     */
    public function testPdoAttributesAreConfiguredCorrectly(): void
    {
        $pdo = (new Database())->getConnection();

        // Ensure exceptions are thrown on errors
        $this->assertSame(PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(PDO::ATTR_ERRMODE));

        // Default fetch mode should be associative array
        $this->assertSame(PDO::FETCH_ASSOC, $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
    }

    /**
     * Test that required environment variables are loaded.
     *
     * @return void
     */
    public function testLoadsEnvironmentVariables(): void
    {
        $host = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? null;
        $name = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? null;
        $user = getenv('DB_USER') ?: $_ENV['DB_USER'] ?? null;

        $this->assertNotEmpty($host, 'DB_HOST should be defined in environment');
        $this->assertNotEmpty($name, 'DB_NAME should be defined in environment');
        $this->assertNotEmpty($user, 'DB_USER should be defined in environment');

        $db = new Database();
        $pdo = $db->getConnection();

        $this->assertInstanceOf(PDO::class, $pdo);
    }

    /**
     * Cleanup after each test.
     *
     * Drops the test table to avoid side effects.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS test_items');
        parent::tearDown();
    }
}
