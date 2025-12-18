<?php

declare(strict_types=1);

namespace Tests\Integration\DB;

use PHPUnit\Framework\TestCase;
use App\DB\RefreshTokenQueries;
use App\DB\Database;
use PDO;

require_once __DIR__ . '/../bootstrap_db.php';

/**
 * Class RefreshTokenQueriesIntegrationTest
 *
 * Integration tests for RefreshTokenQueries using a real database.
 *
 * @package Tests\Integration\DB
 */
class RefreshTokenQueriesIntegrationTest extends TestCase
{
    private PDO $pdo;
    private RefreshTokenQueries $queries;

    /**
     * Setup database connection and tables before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbHost = $_ENV['DB_HOST'] ?? 'db_test';
        assert(is_string($dbHost));

        $dbPort = $_ENV['DB_PORT'] ?? 3306;
        assert(is_numeric($dbPort));
        $dbPort = (int) $dbPort;

        waitForDatabase($dbHost, $dbPort);

        $this->pdo = (new Database())->getConnection();
        $this->queries = new RefreshTokenQueries($this->pdo);

        // Recreate tables
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec('DROP TABLE IF EXISTS refresh_tokens');
        $this->pdo->exec('DROP TABLE IF EXISTS users');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $this->pdo->exec("
            CREATE TABLE users (
                id VARCHAR(64) PRIMARY KEY,
                username VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE refresh_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(64) NOT NULL,
                token_hash VARCHAR(64) NOT NULL,
                expires_at INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY idx_token_hash (token_hash),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }

    /**
     * Cleanup database after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec('DROP TABLE IF EXISTS refresh_tokens');
        $this->pdo->exec('DROP TABLE IF EXISTS users');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        parent::tearDown();
    }

    /**
     * Helper to create a user for testing.
     *
     * @param string $id User ID
     *
     * @return void
     */
    private function createUser(string $id): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, email, password) VALUES ('$id', 'u$id', 'e$id', 'p')");
    }

    /**
     * Test: create and retrieve a token.
     *
     * @return void
     */
    public function testCreateAndRetrieve(): void
    {
        $this->createUser('u1');
        $hash = 'hash123';
        $exp = time() + 3600;

        $this->queries->create('u1', $hash, $exp);

        $row = $this->queries->getByHash($hash);
        $this->assertIsArray($row);
        $this->assertEquals('u1', $row['user_id']);
        $this->assertEquals($exp, $row['expires_at']);
    }

    /**
     * Test: getTokensByUserId returns tokens ordered by ID DESC.
     *
     * @return void
     */
    public function testGetTokensByUserId(): void
    {
        $this->createUser('u1');

        // Insert manually to get IDs easily if needed, or rely on order
        $this->pdo->exec("INSERT INTO refresh_tokens (user_id, token_hash, expires_at) VALUES ('u1', 'h1', 1000)"); // Oldest
        $this->pdo->exec("INSERT INTO refresh_tokens (user_id, token_hash, expires_at) VALUES ('u1', 'h2', 2000)"); // Newest

        // Queries orders by id DESC, so h2 should be first
        $tokens = $this->queries->getTokensByUserId('u1');

        $this->assertCount(2, $tokens);
        // Assuming auto-increment IDs
        $this->assertTrue($tokens[0] > $tokens[1]);
    }

    /**
     * Test: deleteTokens removes specified tokens.
     *
     * @return void
     */
    public function testDeleteTokens(): void
    {
        $this->createUser('u1');
        $this->pdo->exec("INSERT INTO refresh_tokens (user_id, token_hash, expires_at) VALUES ('u1', 'h1', 1000)");
        $id = $this->pdo->lastInsertId();
        assert(is_string($id));

        $this->queries->deleteTokens([$id]);

        $tokens = $this->queries->getTokensByUserId('u1');
        $this->assertEmpty($tokens);
    }

    /**
     * Test: cleanupExpired removes only expired tokens.
     *
     * @return void
     */
    public function testCleanupExpired(): void
    {
        $this->createUser('u1');
        $now = 5000;

        $this->queries->create('u1', 'expired', 1000); // Expired
        $this->queries->create('u1', 'valid', 9000);   // Valid

        $this->queries->cleanupExpired('u1', $now);

        $rowExp = $this->queries->getByHash('expired');
        $this->assertNull($rowExp);

        $rowVal = $this->queries->getByHash('valid');
        $this->assertNotNull($rowVal);
    }

    /**
     * Test: deleteByHash removes specific token.
     *
     * @return void
     */
    public function testDeleteByHash(): void
    {
        $this->createUser('u1');
        $this->queries->create('u1', 'h1', 1000);

        $this->queries->deleteByHash('h1');

        $this->assertNull($this->queries->getByHash('h1'));
    }

    /**
     * Test: deleteAllForUser removes all tokens for a user.
     *
     * @return void
     */
    public function testDeleteAllForUser(): void
    {
        $this->createUser('u1');
        $this->queries->create('u1', 'h1', 1000);
        $this->queries->create('u1', 'h2', 1000);

        $this->queries->deleteAllForUser('u1');

        $tokens = $this->queries->getTokensByUserId('u1');
        $this->assertEmpty($tokens);
    }
}
