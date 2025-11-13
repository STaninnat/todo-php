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

    private DeleteUserService $service;

    private CookieManager $cookieManager;

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

        $storage = new TestCookieStorage();
        $this->cookieManager = new CookieManager($storage);

        $this->service = new DeleteUserService($this->userQueries, $this->cookieManager);
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
        return new Request('POST', '/delete-user', null, null, $body);
    }

    public function testDeleteUserSuccess(): void
    {
        // Insert test user
        $id = 'u123';
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute([$id, 'john', 'john@example.com', password_hash('pass', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest(['user_id' => $id]);

        $this->service->execute($req);

        // Ensure user is deleted
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $this->assertFalse((bool)$stmt->fetch(PDO::FETCH_ASSOC));

        $this->assertNull($this->cookieManager->getAccessToken());
        $this->assertSame('access_token', $this->cookieManager->getLastSetCookieName());
    }

    public function testMissingUserIdThrowsInvalidArgumentException(): void
    {
        $req = $this->makeRequest([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required');

        $this->service->execute($req);
    }

    public function testDatabaseFailureThrowsRuntimeException(): void
    {
        // Simulate DB failure with subclass
        $failingQueries = new class($this->pdo) extends UserQueries {
            public function deleteUser(string $id): QueryResult
            {
                return QueryResult::fail(['Simulated DB error']);
            }
        };

        $service = new DeleteUserService($failingQueries, $this->cookieManager);
        $req = $this->makeRequest(['user_id' => 'any']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/delete user/i');

        try {
            $service->execute($req);
        } finally {
            $this->assertNull($this->cookieManager->getAccessToken());
        }
    }

    public function testDeleteUserSuccessWithCookieClear(): void
    {
        $id = 'u1';
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute([$id, 'alice', 'alice@example.com', password_hash('pass', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest(['user_id' => $id]);
        $this->service->execute($req);

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $this->assertFalse((bool)$stmt->fetch(PDO::FETCH_ASSOC));

        $this->assertNull($this->cookieManager->getAccessToken());
        $this->assertSame('access_token', $this->cookieManager->getLastSetCookieName());
    }

    public function testDeleteUserDatabaseFailureDoesNotClearCookie(): void
    {
        $failingQueries = new class($this->pdo) extends UserQueries {
            public function deleteUser(string $id): QueryResult
            {
                return QueryResult::fail(['Simulated DB error']);
            }
        };

        $service = new DeleteUserService($failingQueries, $this->cookieManager);
        $req = $this->makeRequest(['user_id' => 'u1']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/delete user/i');

        try {
            $service->execute($req);
        } finally {
            $this->assertNull($this->cookieManager->getAccessToken());
        }
    }
}
