<?php

declare(strict_types=1);

namespace Tests\Integration\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\RefreshTokenService;
use App\DB\RefreshTokenQueries;
use App\DB\Database;
use App\Utils\JwtService;
use PDO;

require_once __DIR__ . '/../bootstrap_db.php';

/**
 * Class RefreshTokenServiceIntegrationTest
 *
 * Integration tests for {@see RefreshTokenService}.
 *
 * Verifies database operations for creating, verifying, and revoking refresh tokens.
 *
 * @package Tests\Integration\Utils
 */
class RefreshTokenServiceIntegrationTest extends TestCase
{
    /** @var PDO Database connection */
    private PDO $pdo;

    /** @var RefreshTokenService Service under test */
    private RefreshTokenService $service;

    /**
     * Set up test environment.
     *
     * - Connects to database
     * - Creates `users` and `refresh_tokens` tables
     * - Initializes service with real DB and JWT deps
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

        // Recreate tables
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec('DROP TABLE IF EXISTS refresh_tokens');
        $this->pdo->exec('DROP TABLE IF EXISTS users');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        // Create Users Table (simplified for relation)
        $this->pdo->exec("
            CREATE TABLE users (
                id VARCHAR(64) PRIMARY KEY,
                username VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create Refresh Tokens Table
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

        $jwt = new JwtService('test-secret');
        $queries = new RefreshTokenQueries($this->pdo);
        $this->service = new RefreshTokenService($queries, $jwt);
    }

    /**
     * Clean up database after tests.
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
     * Test that create() persists a token record in the database.
     *
     * @return void
     */
    public function testCreatePersistsToken(): void
    {
        $userId = 'u1';
        $this->pdo->exec("INSERT INTO users (id, username, email, password) VALUES ('$userId', 'u', 'e', 'p')");

        $token = $this->service->create($userId);

        $stmt = $this->pdo->query("SELECT * FROM refresh_tokens WHERE user_id = '$userId'");
        $this->assertNotFalse($stmt);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($row);
        $this->assertIsArray($row);
        // We can't verify hash easily without exposing hashing logic, but we know it persisted.
    }

    /**
     * Test that verify() returns the correct user ID for a valid token.
     *
     * @return void
     */
    public function testVerifyReturnsUserId(): void
    {
        $userId = 'u1';
        $this->pdo->exec("INSERT INTO users (id, username, email, password) VALUES ('$userId', 'u', 'e', 'p')");

        $token = $this->service->create($userId);
        $verifiedId = $this->service->verify($token);

        $this->assertEquals($userId, $verifiedId);
    }

    /**
     * Test that verify() throws exception for a non-existent/invalid token.
     *
     * @return void
     */
    public function testVerifyThrowsOnInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->verify('invalid_token');
    }

    /**
     * Test that verify() throws exception and revokes/deletes an expired token.
     *
     * @return void
     */
    public function testVerifyRevokesOnExpiry(): void
    {
        $userId = 'u1';
        $this->pdo->exec("INSERT INTO users (id, username, email, password) VALUES ('$userId', 'u', 'e', 'p')");

        // Manually insert an expired token
        $expiredToken = 'expired';
        $jwt = new JwtService('test-secret');
        $hash = $jwt->hashRefreshToken($expiredToken);
        $exp = time() - 3600;

        $this->pdo->prepare("INSERT INTO refresh_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)")
            ->execute([$userId, $hash, $exp]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh token expired');

        try {
            $this->service->verify($expiredToken);
        } finally {
            // Assert it was deleted
            $stmt = $this->pdo->prepare("SELECT * FROM refresh_tokens WHERE token_hash = ?");
            $stmt->execute([$hash]);
            $this->assertFalse($stmt->fetch());
        }
    }

    /**
     * Test that revoke() deletes the token from the database.
     *
     * @return void
     */
    public function testRevokeDeletesToken(): void
    {
        $userId = 'u1';
        $this->pdo->exec("INSERT INTO users (id, username, email, password) VALUES ('$userId', 'u', 'e', 'p')");

        $token = $this->service->create($userId);
        $this->service->revoke($token);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->verify($token);
    }
}
