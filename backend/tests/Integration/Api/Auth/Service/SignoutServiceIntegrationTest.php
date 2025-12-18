<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Auth\Service;

use App\Api\Auth\Service\SignoutService;
use App\Utils\RefreshTokenService;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use App\DB\Database;
use App\DB\RefreshTokenQueries;
use PHPUnit\Framework\TestCase;
use Tests\Integration\Api\Helper\TestCookieStorage;
use PDO;

/**
 * Class SignoutServiceIntegrationTest
 *
 * Integration tests for the SignoutService class.
 *
 * This suite verifies:
 * - Access token cookie is cleared upon signout
 * - Signout is idempotent (works even when cookie does not exist)
 *
 * @package Tests\Integration\Api\Auth\Service
 */
class SignoutServiceIntegrationTest extends TestCase
{
    /**
     * @var CookieManager Cookie manager instance for inspecting clear-cookie behavior.
     */
    private CookieManager $cookieManager;
    private RefreshTokenService $refreshTokenService;
    private PDO $pdo;

    /**
     * @var SignoutService Service under test.
     */
    private SignoutService $service;

    /**
     * @var TestCookieStorage Cookie storage used for testing set/delete operations.
     */
    private TestCookieStorage $storage;

    /**
     * Setup test environment.
     *
     * Initializes cookie storage, cookie manager, and SignoutService instance.
     * Pre-sets an access_token cookie to simulate an authenticated session.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = new TestCookieStorage();
        $this->cookieManager = new CookieManager($this->storage);

        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->refreshTokenService = new RefreshTokenService(new RefreshTokenQueries($this->pdo), new JwtService('test-secret'));

        $this->service = new SignoutService($this->cookieManager, $this->refreshTokenService);

        // Pre-set cookie to simulate existing session
        $this->storage->set('access_token', 'dummy_token', time() + 3600);
    }

    /**
     * Test successful clearing of access token cookie.
     *
     * Ensures:
     * - CookieManager reports correct last cleared cookie
     * - access_token is removed from storage
     *
     * @return void
     */
    public function testExecuteClearsAccessTokenCookie(): void
    {
        $this->service->execute();

        // Confirm token removal in storage
        $this->assertNull($this->storage->get('access_token'));
    }


    /**
     * Test successful clearing of refresh token cookie and revocation in DB.
     *
     * @return void
     */
    public function testExecuteRevokesAndClearsRefreshToken(): void
    {
        // Setup DB tables
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec('DROP TABLE IF EXISTS refresh_tokens');
        $this->pdo->exec('DROP TABLE IF EXISTS users');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $this->pdo->exec("CREATE TABLE users (id VARCHAR(64) PRIMARY KEY, username VARCHAR(255), email VARCHAR(255), password VARCHAR(255))");
        $this->pdo->exec("
            CREATE TABLE refresh_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(64) NOT NULL,
                token_hash VARCHAR(64), 
                expires_at INTEGER,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        $this->pdo->exec("INSERT INTO users VALUES ('u1', 'u', 'e', 'p')");

        // Create a refresh token
        $token = $this->refreshTokenService->create('u1');

        // Mock current cookie state
        $this->storage->set('refresh_token', $token, time() + 3600);

        // Execute Signout
        $this->service->execute();

        // Assert cookie cleared
        $this->assertNull($this->storage->get('refresh_token'));

        // Assert DB revocation (verify should fail)
        $this->expectException(\InvalidArgumentException::class);
        $this->refreshTokenService->verify($token);
    }
    /**
     * Test behavior when cookie does not exist beforehand.
     *
     * Ensures signout behaves idempotently and does not fail.
     *
     * @return void
     */
    public function testExecuteWhenCookieAlreadyMissing(): void
    {
        // Remove cookie to simulate missing access token
        $this->storage->delete('access_token');

        $this->service->execute();

        // Still expected to have no cookie after execution
        $this->assertNull($this->storage->get('access_token'));
    }
}
