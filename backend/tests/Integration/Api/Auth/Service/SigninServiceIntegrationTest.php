<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Auth\Service;

use App\Api\Auth\Service\SigninService;
use App\Api\Request;
use App\DB\Database;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use Tests\Integration\Api\Helper\TestCookieStorage;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once __DIR__ . '/../../../bootstrap_db.php';

/**
 * Class SigninServiceIntegrationTest
 *
 * Integration tests for SigninService.
 *
 * This suite validates:
 * - Successful signin flow and token generation
 * - Handling of missing fields or invalid credentials
 * - Behavior when database returns corrupted/invalid user data
 * - Error handling when DB operations fail
 *
 * @package Tests\Integration\Api\Auth\Service
 */
class SigninServiceIntegrationTest extends TestCase
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
     * @var JwtService Handles issuance and decoding of JWT tokens.
     */
    private JwtService $jwt;

    /**
     * @var CookieManager Cookie manager used to store signin token.
     */
    private CookieManager $cookieManager;

    /**
     * @var SigninService Service under test.
     */
    private SigninService $service;

    /**
     * Setup test environment.
     *
     * Initializes the database connection, recreates the users table,
     * and prepares dependencies for SigninUserService.
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
        $dbPort = (int)$dbPort;

        // Wait for test DB readiness
        waitForDatabase($dbHost, $dbPort);

        $this->pdo = (new Database())->getConnection();
        $this->userQueries = new UserQueries($this->pdo);
        $this->jwt = new JwtService('test-secret-key');

        // Reset table to maintain test isolation
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

        $this->service = new SigninService(
            $this->userQueries,
            $this->cookieManager,
            $this->jwt
        );
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
     * Helper method to construct Request objects.
     *
     * @param array<string, mixed> $body
     * 
     * @return Request
     */
    private function makeRequest(array $body): Request
    {
        return new Request('POST', '/signin', null, null, $body);
    }

    /**
     * Test successful signin flow.
     *
     * Ensures:
     * - Valid credentials generate a JWT
     * - Token is stored in cookie manager
     * - Token payload matches expected user data
     *
     * @return void
     */
    public function testSigninSuccess(): void
    {
        $id = 'u123';
        $username = 'johnny';
        $email = 'johnny@example.com';
        $hash = password_hash('secure123', PASSWORD_DEFAULT);

        // Insert user to simulate a real signin scenario
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute([$id, $username, $email, $hash]);

        $req = $this->makeRequest([
            'username' => 'johnny',
            'password' => 'secure123',
        ]);

        $this->service->execute($req);

        $token = $this->cookieManager->getAccessToken();
        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        // Decode token to verify identity
        $payload = $this->jwt->decodeStrict($token);
        $this->assertSame($id, $payload['id']);
    }

    /**
     * Test that incorrect password triggers an exception.
     *
     * @return void
     */
    public function testInvalidPasswordThrowsException(): void
    {
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute(['id1', 'bob', 'b@example.com', password_hash('realpass', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest([
            'username' => 'bob',
            'password' => 'wrongpass',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid username or password.');

        // Execution should fail due to password mismatch
        $this->service->execute($req);
    }

    /**
     * Test login attempt on a non-existent user.
     *
     * @return void
     */
    public function testUnknownUserThrowsException(): void
    {
        $req = $this->makeRequest([
            'username' => 'ghost',
            'password' => 'irrelevant',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid username or password.');

        // Should fail because user does not exist
        $this->service->execute($req);
    }

    /**
     * Test missing password field validation.
     *
     * @return void
     */
    public function testMissingPasswordThrowsException(): void
    {
        $req = $this->makeRequest(['username' => 'someone']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->execute($req);
    }

    /**
     * Test missing username field validation.
     *
     * @return void
     */
    public function testMissingUsernameThrowsException(): void
    {
        $req = $this->makeRequest(['password' => 'pass']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->execute($req);
    }

    /**
     * Test valid JWT payload structure after signin.
     *
     * @return void
     */
    public function testGenerateValidJwtToken(): void
    {
        $id = 'jwt123';
        $username = 'jwtuser';
        $email = 'jwt@example.com';
        $hash = password_hash('abc12345', PASSWORD_DEFAULT);

        // Insert user for signin
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute([$id, $username, $email, $hash]);

        $req = $this->makeRequest([
            'username' => 'jwtuser',
            'password' => 'abc12345',
        ]);

        $this->service->execute($req);

        $token = $this->cookieManager->getAccessToken();
        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        $payload = $this->jwt->decodeStrict($token);

        // Validate payload contains required fields
        $this->assertArrayHasKey('id', $payload);
        $this->assertSame($id, $payload['id']);
    }

    /**
     * Test corrupted user data returned from DB.
     *
     * Simulates scenario where DB schema is inconsistent
     * (e.g., missing password column).
     *
     * @return void
     */
    public function testInvalidUserDataThrowsRuntimeException(): void
    {
        // Remove password column to break expected structure
        $this->pdo->exec("ALTER TABLE users DROP COLUMN password");
        $this->pdo->prepare("INSERT INTO users (id, username, email) VALUES (?, ?, ?)")
            ->execute(['badid', 'broken', 'broken@example.com']);

        $req = $this->makeRequest([
            'username' => 'broken',
            'password' => 'pass',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid user data returned from getUserByName.');

        // Should fail due to schema mismatch
        $this->service->execute($req);
    }

    /**
     * Test DB failure scenario where user table does not exist.
     *
     * @return void
     */
    public function testDatabaseFailThrowsRuntimeException(): void
    {
        // Drop table to simulate DB failure
        $this->pdo->exec('DROP TABLE IF EXISTS users');

        $req = $this->makeRequest([
            'username' => 'any',
            'password' => 'x',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/fetch user/i');

        // Execution expected to fail during user fetch
        $this->service->execute($req);
    }
}
