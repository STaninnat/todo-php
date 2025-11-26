<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Auth\Service;

use App\Api\Auth\Service\UpdateUserService;
use App\Api\Request;
use App\DB\Database;
use App\DB\QueryResult;
use App\DB\UserQueries;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once __DIR__ . '/../../../bootstrap_db.php';

/**
 * Class UpdateUserServiceIntegrationTest
 *
 * Integration tests for the UpdateUserService class.
 *
 * This suite verifies:
 * - Successful updates of username and email
 * - Validation for missing or invalid fields
 * - Duplicate username/email handling
 * - Proper handling of whitespace and long input
 * - Exception propagation for DB failures or invalid return data
 *
 * @package Tests\Integration\Api\Auth\Service
 */
class UpdateUserServiceIntegrationTest extends TestCase
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
     * @var UpdateUserService Service under test
     */
    private UpdateUserService $service;

    /**
     * Setup test environment.
     *
     * Initializes the database connection, recreates the users table,
     * and prepares dependencies for UpdateUserService.
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
        $this->userQueries = new UserQueries($this->pdo);
        $this->service = new UpdateUserService($this->userQueries);

        $this->pdo->exec('DROP TABLE IF EXISTS users');
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

        // Insert a sample user
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute(['u1', 'john', 'john@example.com', password_hash('pass', PASSWORD_DEFAULT)]);
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
        $this->pdo->exec('DROP TABLE IF EXISTS users');
        parent::tearDown();
    }

    /**
     * Helper to create Request objects.
     *
     * @param array<string, mixed> $body
     * 
     * @return Request
     */
    private function makeRequest(array $body): Request
    {
        return new Request('POST', '/update-user', null, null, $body);
    }

    /**
     * Test successful update of username and email.
     *
     * Verifies both DB persistence and returned data.
     *
     * @return void
     */
    public function testUpdateSuccess(): void
    {
        $req = $this->makeRequest([
            'user_id' => 'u1',
            'username' => 'john_new',
            'email' => 'john_new@example.com',
        ]);

        $result = $this->service->execute($req);

        $this->assertSame('john_new', $result['username']);
        $this->assertSame('john_new@example.com', $result['email']);

        $stmt = $this->pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute(['u1']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($user);
        $this->assertArrayHasKey('username', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertSame('john_new', $user['username']);
        $this->assertSame('john_new@example.com', $user['email']);
    }

    /**
     * Test that duplicate username triggers RuntimeException.
     *
     * @return void
     */
    public function testDuplicateUsernameThrowsException(): void
    {
        // Insert another user with target username
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute(['u2', 'existing', 'exist@example.com', password_hash('x', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest([
            'user_id' => 'u1',
            'username' => 'existing',
            'email' => 'new@example.com',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already exists');

        $this->service->execute($req);
    }

    /**
     * Test that duplicate email triggers RuntimeException.
     *
     * @return void
     */
    public function testDuplicateEmailThrowsException(): void
    {
        // Insert another user with target email
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute(['u3', 'unique', 'dup@example.com', password_hash('x', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest([
            'user_id' => 'u1',
            'username' => 'newuser',
            'email' => 'dup@example.com',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already exists');

        $this->service->execute($req);
    }

    /**
     * Test empty username triggers InvalidArgumentException.
     *
     * @return void
     */
    public function testEmptyUsernameThrowsException(): void
    {
        $req = $this->makeRequest([
            'user_id' => 'u1',
            'username' => '',
            'email' => 'new@example.com',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Username is required');

        $this->service->execute($req);
    }

    /**
     * Test empty email triggers InvalidArgumentException.
     *
     * @return void
     */
    public function testEmptyEmailThrowsException(): void
    {
        $req = $this->makeRequest([
            'user_id' => 'u1',
            'username' => 'newuser',
            'email' => '',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->execute($req);
    }

    /**
     * Test username/email with leading/trailing whitespace.
     *
     * Ensures trimming or DB stored values are handled correctly.
     *
     * @return void
     */
    public function testWhitespaceUsernameAndEmail(): void
    {
        $req = $this->makeRequest([
            'user_id' => 'u1',
            'username' => '   john_new   ',
            'email' => '  john_new@example.com  ',
        ]);

        $result = $this->service->execute($req);

        // DB stores as-is, so check trimmed manually if needed
        $stmt = $this->pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute(['u1']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($user);
        $this->assertArrayHasKey('username', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertSame('john_new', $user['username']);
        $this->assertSame('john_new@example.com', $user['email']);
        $this->assertSame($user['username'], $result['username']);
        $this->assertSame($user['email'], $result['email']);
    }

    /**
     * Test very long username/email triggers InvalidArgumentException.
     *
     * @return void
     */
    public function testVeryLongUsernameAndEmailThrowsException(): void
    {
        $longUsername = str_repeat('a', 250);
        $longEmail = str_repeat('b', 240) . '@example.com';

        $req = $this->makeRequest([
            'user_id' => 'u1',
            'username' => $longUsername,
            'email' => $longEmail,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Valid email is required');

        $this->service->execute($req);
    }

    /**
     * Test missing user_id triggers InvalidArgumentException.
     *
     * @return void
     */
    public function testMissingUserIdThrowsException(): void
    {
        $req = $this->makeRequest([
            'username' => 'newuser',
            'email' => 'new@example.com',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required');

        $this->service->execute($req);
    }

    /**
     * Test missing username triggers InvalidArgumentException.
     *
     * @return void
     */
    public function testMissingUsernameThrowsException(): void
    {
        $req = $this->makeRequest([
            'user_id' => 'u1',
            'email' => 'new@example.com',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Username is required');

        $this->service->execute($req);
    }

    /**
     * Test invalid email triggers InvalidArgumentException.
     *
     * @return void
     */
    public function testInvalidEmailThrowsException(): void
    {
        $req = $this->makeRequest([
            'user_id' => 'u1',
            'username' => 'newuser',
            'email' => 'not-an-email',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->execute($req);
    }

    /**
     * Test that updateUser returning invalid structure triggers RuntimeException.
     *
     * @return void
     */
    public function testUpdateReturnsInvalidDataThrowsRuntimeException(): void
    {
        $mockQueries = $this->createMock(UserQueries::class);
        $mockQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 1));
        $mockQueries->method('updateUser')->willReturn(QueryResult::ok(['bad' => 'structure'], 1));

        $service = new UpdateUserService($mockQueries);

        $req = $this->makeRequest([
            'user_id' => 'u1',
            'username' => 'broken',
            'email' => 'broken@example.com',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid data returned from updateUser');

        $service->execute($req);
    }

    /**
     * Test database failure when checking user existence triggers RuntimeException.
     *
     * @return void
     */
    public function testDatabaseFailureThrowsRuntimeException(): void
    {
        $mockQueries = $this->createMock(UserQueries::class);
        $mockQueries->method('checkUserExists')->willReturn(QueryResult::fail(['DB error']));
        $service = new UpdateUserService($mockQueries);

        $req = $this->makeRequest([
            'user_id' => 'u1',
            'username' => 'any',
            'email' => 'any@example.com',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/check user existence/i');

        $service->execute($req);
    }
}
