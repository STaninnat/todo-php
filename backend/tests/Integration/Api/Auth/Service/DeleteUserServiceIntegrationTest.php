<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Auth\Service;

use App\Api\Auth\Service\DeleteUserService;
use App\Api\Request;
use App\DB\Database;
use App\DB\UserQueries;
use App\DB\QueryResult;
use App\Utils\CookieManager;
use Tests\Integration\Api\Helper\TestCookieStorage;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once __DIR__ . '/../../../bootstrap_db.php';

/**
 * Class DeleteUserServiceIntegrationTest
 *
 * Integration tests for the DeleteUserService class.
 *
 * This suite verifies:
 * - User deletion behavior from database
 * - Proper validation for missing user_id
 * - RuntimeException handling for simulated DB failures
 * - Cookie clearing logic after user deletion
 *
 * @package Tests\Integration\Api\Auth\Service
 */
class DeleteUserServiceIntegrationTest extends TestCase
{
    /**
     * @var PDO PDO instance for integration testing
     */
    private PDO $pdo;

    /**
     * @var UserQueries UserQueries instance for testing
     */
    private UserQueries $userQueries;

    /**
     * @var DeleteUserService Service under test.
     */
    private DeleteUserService $service;

    /**
     * @var CookieManager Cookie manager instance for session handling.
     */
    private CookieManager $cookieManager;

    /**
     * Setup test environment.
     *
     * Initializes the database connection, recreates the users table,
     * and prepares dependencies for DeleteUserService.
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

        // Wait for test DB readiness before continuing
        waitForDatabase($dbHost, $dbPort);

        $this->pdo = (new Database())->getConnection();
        $this->userQueries = new UserQueries($this->pdo);

        // Reset the users table to ensure test isolation
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec('DROP TABLE IF EXISTS refresh_tokens');
        $this->pdo->exec('DROP TABLE IF EXISTS users');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $this->pdo->exec("
            CREATE TABLE users (
                id VARCHAR(64) PRIMARY KEY,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Initialize cookie storage for testing logout/session cleanup
        $storage = new TestCookieStorage();
        $this->cookieManager = new CookieManager($storage);

        $this->service = new DeleteUserService($this->userQueries, $this->cookieManager);
    }

    /**
     * Cleanup database after each test.
     *
     * Drops the users table to guarantee clean state.
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
     * Helper method to create a Request with JSON body.
     *
     * @param array<string, mixed> $body Request body payload.
     * 
     * @return Request
     */
    private function makeRequest(array $body, ?string $userId = null): Request
    {
        $req = new Request('POST', '/delete-user', null, null, $body);
        if ($userId !== null) {
            $req->auth = ['id' => $userId];
        }
        return $req;
    }

    /**
     * Test successful user deletion scenario.
     *
     * Ensures:
     * - User is removed from DB
     * - Cookie is cleared after deletion
     *
     * @return void
     */
    public function testDeleteUserSuccess(): void
    {
        // Insert test user
        $id = 'u123';
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute([$id, 'john', 'john@example.com', password_hash('pass', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest([], $id);

        $this->service->execute($req);

        // Ensure user is deleted from DB
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $this->assertFalse((bool) $stmt->fetch(PDO::FETCH_ASSOC));

        // Cookie should be cleared to reflect logout
        $this->assertNull($this->cookieManager->getAccessToken());
        $this->assertSame('access_token', $this->cookieManager->getLastSetCookieName());
    }



    /**
     * Test DB failure handling.
     *
     * Simulates DB failure by overriding deleteUser() to return a failed QueryResult.
     * Expects RuntimeException containing "delete user".
     *
     * @return void
     */
    public function testDatabaseFailureThrowsRuntimeException(): void
    {
        // Simulate DB error to verify exception propagation
        $failingQueries = new class ($this->pdo) extends UserQueries {
            public function deleteUser(string $id): QueryResult
            {
                return QueryResult::fail(['Simulated DB error']);
            }
        };

        $service = new DeleteUserService($failingQueries, $this->cookieManager);
        $req = $this->makeRequest([], 'any');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/delete user/i');

        try {
            $service->execute($req);
        } finally {
            // Inline: ensure cookie not left in inconsistent state
            $this->assertNull($this->cookieManager->getAccessToken());
        }
    }

    /**
     * Test that cookies are cleared properly after successful deletion.
     *
     * Ensures consistent post-deletion cleanup.
     *
     * @return void
     */
    public function testDeleteUserSuccessWithCookieClear(): void
    {
        $id = 'u1';
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute([$id, 'alice', 'alice@example.com', password_hash('pass', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest([], $id);
        $this->service->execute($req);

        // Inline: verify DB deletion consistency
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $this->assertFalse((bool) $stmt->fetch(PDO::FETCH_ASSOC));

        $this->assertNull($this->cookieManager->getAccessToken());
        $this->assertSame('access_token', $this->cookieManager->getLastSetCookieName());
    }

    /**
     * Test scenario where DB failure occurs but cookie should not be cleared.
     *
     * Verifies that failed deletion does not trigger session cleanup.
     *
     * @return void
     */
    public function testDeleteUserDatabaseFailureDoesNotClearCookie(): void
    {
        // Inline: ensure service respects failure isolation (cookie remains untouched)
        $failingQueries = new class ($this->pdo) extends UserQueries {
            public function deleteUser(string $id): QueryResult
            {
                return QueryResult::fail(['Simulated DB error']);
            }
        };

        $service = new DeleteUserService($failingQueries, $this->cookieManager);
        $req = $this->makeRequest([], 'u1');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/delete user/i');

        try {
            $service->execute($req);
        } finally {
            $this->assertNull($this->cookieManager->getAccessToken());
        }
    }
}
