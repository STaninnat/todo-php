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

class GetUserServiceIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserQueries $userQueries;
    private GetUserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHost = $_ENV['DB_HOST'] ?? 'db_test';
        assert(is_string($dbHost));

        $dbPort = $_ENV['DB_PORT'] ?? 3306;
        assert(is_numeric($dbPort));
        $dbPort = (int)$dbPort;

        waitForDatabase($dbHost, $dbPort);

        $this->pdo = (new Database())->getConnection();
        $this->userQueries = new UserQueries($this->pdo);

        // Reset users table
        $this->pdo->exec('DROP TABLE IF EXISTS users');
        $this->pdo->exec("
            CREATE TABLE users (
                id VARCHAR(64) PRIMARY KEY,
                username VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->service = new GetUserService($this->userQueries);
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS users');
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $body
     */
    private function makeRequest(array $body): Request
    {
        return new Request('POST', '/get-user', null, null, $body);
    }

    public function testGetUserSuccess(): void
    {
        // Insert test user
        $id = 'u123';
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute([$id, 'john', 'john@example.com', password_hash('pass', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest(['user_id' => $id]);
        $result = $this->service->execute($req);

        $this->assertSame([
            'username' => 'john',
            'email' => 'john@example.com'
        ], $result);
    }

    public function testMissingUserIdThrowsInvalidArgumentException(): void
    {
        $req = $this->makeRequest([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required');

        $this->service->execute($req);
    }

    public function testUserNotFoundThrowsRuntimeException(): void
    {
        $req = $this->makeRequest(['user_id' => 'nonexistent']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch user: No data or changes found.');

        $this->service->execute($req);
    }

    public function testDatabaseFailureThrowsRuntimeException(): void
    {
        $userQueriesMock = $this->createMock(UserQueries::class);
        $userQueriesMock->method('getUserById')
            ->willReturn(QueryResult::fail(['Simulated DB error']));

        $service = new GetUserService($userQueriesMock);

        $req = $this->makeRequest(['user_id' => 'u1']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/fetch user/i');

        $service->execute($req);
    }

    public function testInvalidUserDataThrowsRuntimeException(): void
    {
        $userQueriesMock = $this->createMock(UserQueries::class);
        $userQueriesMock->method('getUserById')
            ->willReturn(QueryResult::ok(['invalid_field' => 'oops'], 1));

        $service = new GetUserService($userQueriesMock);

        $req = $this->makeRequest(['user_id' => 'u1']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/invalid user data/i');

        $service->execute($req);
    }
}
