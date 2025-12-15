<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Auth\Service;

use App\Api\Auth\Service\GetUserService;
use App\Api\Request;
use App\DB\Database;
use App\DB\UserQueries;
use App\DB\QueryResult;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once __DIR__ . '/../../../bootstrap_db.php';

/**
 * Class GetUserServiceIntegrationTest
 *
 * Integration tests for GetUserService.
 *
 * Verifies:
 * - Correct user data retrieval
 * - Validation for missing user_id
 * - Behavior when user not found
 * - Error propagation when DB fails
 * - Data integrity validation
 *
 * @package Tests\Integration\Api\Auth\Service
 */
class GetUserServiceIntegrationTest extends TestCase
{
    /**
     * @var PDO Database connection for integration tests.
     */
    private PDO $pdo;

    /**
     * @var UserQueries UserQueries instance used by the service.
     */
    private UserQueries $userQueries;

    /**
     * @var GetUserService Service under test.
     */
    private GetUserService $service;

    /**
     * Setup testing environment.
     *
     * Initializes the database connection, recreates the users table,
     * and prepares dependencies for GetUserService.
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

        // Ensure DB container is ready before executing queries
        waitForDatabase($dbHost, $dbPort);

        $this->pdo = (new Database())->getConnection();
        $this->userQueries = new UserQueries($this->pdo);

        // Reset table to guarantee clean test state
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

        $this->service = new GetUserService($this->userQueries);
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
        // Ensure next test starts clean
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec('DROP TABLE IF EXISTS refresh_tokens');
        $this->pdo->exec('DROP TABLE IF EXISTS users');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        parent::tearDown();
    }

    /**
     * Helper to create Request containing JSON payload.
     *
     * @param array<string, mixed> $body
     * 
     * @return Request
     */
    private function makeRequest(array $body, ?string $userId = null): Request
    {
        $req = new Request('POST', '/get-user', null, null, $body);
        if ($userId !== null) {
            $req->auth = ['id' => $userId];
        }
        return $req;
    }

    /**
     * Test successful user retrieval.
     *
     * Ensures the service returns correct fields only.
     *
     * @return void
     */
    public function testGetUserSuccess(): void
    {
        // Insert user fixture for lookup
        $id = 'u123';
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute([$id, 'john', 'john@example.com', password_hash('pass', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest([], $id);

        $result = $this->service->execute($req);

        // Service must return only public user fields
        $this->assertSame([
            'username' => 'john',
            'email' => 'john@example.com'
        ], $result);
    }



    /**
     * Test behavior when user does not exist.
     *
     * Expects RuntimeException with missing-user message.
     *
     * @return void
     */
    public function testUserNotFoundThrowsRuntimeException(): void
    {
        $req = $this->makeRequest([], 'nonexistent');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch user: No data or changes found.');

        $this->service->execute($req);
    }

    /**
     * Test DB failure scenario.
     *
     * Mock getUserById() to return fail() result, simulating DB error.
     *
     * @return void
     */
    public function testDatabaseFailureThrowsRuntimeException(): void
    {
        // Inline: mock direct DB failure path
        $userQueriesMock = $this->createMock(UserQueries::class);
        $userQueriesMock->method('getUserById')
            ->willReturn(QueryResult::fail(['Simulated DB error']));

        $service = new GetUserService($userQueriesMock);

        $req = $this->makeRequest([], 'u1');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/fetch user/i');

        $service->execute($req);
    }

    /**
     * Test invalid user data shape.
     *
     * Ensures service validates expected DB structure.
     *
     * @return void
     */
    public function testInvalidUserDataThrowsRuntimeException(): void
    {
        // Simulate DB returning unexpected field structure
        $userQueriesMock = $this->createMock(UserQueries::class);
        $userQueriesMock->method('getUserById')
            ->willReturn(QueryResult::ok(['invalid_field' => 'oops'], 1));

        $service = new GetUserService($userQueriesMock);

        $req = $this->makeRequest([], 'u1');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/invalid user data/i');

        $service->execute($req);
    }
}
