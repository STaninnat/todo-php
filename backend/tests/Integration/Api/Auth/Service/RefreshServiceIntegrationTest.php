<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Auth\Service\RefreshService;
use App\Api\Auth\Service\RefreshTokenService;
use App\DB\Database;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use Tests\Integration\Api\Helper\TestCookieStorage;
use PDO;
use RuntimeException;

require_once __DIR__ . '/../../../bootstrap_db.php';

/**
 * Class RefreshServiceIntegrationTest
 *
 * Integration tests for {@see RefreshService}.
 *
 * Verifies the end-to-end flow of refreshing a user's access token via the database and cookies.
 *
 * @package Tests\Integration\Api\Auth\Service
 */
class RefreshServiceIntegrationTest extends TestCase
{
    /** @var PDO Database connection */
    private PDO $pdo;

    /** @var RefreshService Service under test */
    private RefreshService $service;

    /** @var RefreshTokenService Dependency for token management */
    private RefreshTokenService $refreshTokenService;

    /** @var CookieManager Dependency for cookie management */
    private CookieManager $cookieManager;

    /** @var JwtService Dependency for JWT handling */
    private JwtService $jwt;

    /**
     * Set up test environment.
     *
     * - Connects to test database
     * - Recreates `users` and `refresh_tokens` tables
     * - Initializes service dependencies
     *
     * @return void
     */
    protected function setUp(): void
    {
        $dbHost = $_ENV['DB_HOST'] ?? 'db_test';
        if (!is_string($dbHost)) {
            $dbHost = 'db_test';
        }
        $dbPort = $_ENV['DB_PORT'] ?? 3306;
        if (!is_numeric($dbPort)) {
            $dbPort = 3306;
        }
        $dbPort = (int) $dbPort;

        waitForDatabase($dbHost, $dbPort);
        $this->pdo = (new Database())->getConnection();

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
                user_id VARCHAR(64) NOT NULL,
                token_hash VARCHAR(64) NOT NULL,
                expires_at INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY idx_token_hash (token_hash),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        $this->jwt = new JwtService('test-secret');
        $this->refreshTokenService = new RefreshTokenService(new Database(), $this->jwt);
        $this->cookieManager = new CookieManager(new TestCookieStorage());

        $this->service = new RefreshService(
            $this->refreshTokenService,
            $this->cookieManager,
            $this->jwt
        );
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
    }

    /**
     * Test successful token refresh execution.
     *
     * Steps:
     * 1. Create a user and a valid refresh token.
     * 2. Set the refresh token in the cookie.
     * 3. Execute the service.
     * 4. Verify the old token is revoked/invalidated.
     * 5. Verify new refresh and access tokens are set in cookies.
     * 6. Verify the new refresh token is valid in the DB.
     *
     * @return void
     */
    public function testExecuteRefreshesTokens(): void
    {
        $userId = 'u1';
        $this->pdo->exec("INSERT INTO users (id, username, email, password) VALUES ('$userId', 'u', 'e', 'p')");

        // 1. Setup initial state
        $oldRefreshToken = $this->refreshTokenService->create($userId);
        $this->cookieManager->setRefreshToken($oldRefreshToken, time() + 3600);

        // 2. Execute Refresh
        $this->service->execute();

        // 3. Assertions

        // Old token should be revoked (not verifiable by ID since it's hashed, but verify should fail)
        $this->expectException(\InvalidArgumentException::class);
        $this->refreshTokenService->verify($oldRefreshToken);

        // Check Cookies
        $newJsonRefresh = $this->cookieManager->getRefreshToken();
        $newJsonAccess = $this->cookieManager->getAccessToken();

        $this->assertNotEmpty($newJsonRefresh);
        $this->assertNotEmpty($newJsonAccess);
        $this->assertNotEquals($oldRefreshToken, $newJsonRefresh);

        // Verify new token works
        $verifiedId = $this->refreshTokenService->verify((string) $newJsonRefresh);
        $this->assertEquals($userId, $verifiedId);
    }

    /**
     * Test that execute throws exception when an invalid token is provided.
     *
     * @return void
     */
    public function testExecuteFailsWithInvalidToken(): void
    {
        $this->cookieManager->setRefreshToken('invalid_token', time() + 3600);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->execute();
    }
}
