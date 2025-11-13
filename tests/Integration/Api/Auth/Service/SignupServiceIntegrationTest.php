<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Auth\Service;

use App\Api\Auth\Service\SignupService;
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
 * Class SignupServiceIntegrationTest
 *
 * Integration test suite for SignupService.
 *
 * Covers:
 * - Successful signup flow
 * - Duplicate username/email handling
 * - Input validation behavior
 * - JWT issuance and correctness
 * - Database failure handling and cookie side effects
 *
 * @package Tests\Integration\Api\Auth\Service
 */
class SignupServiceIntegrationTest extends TestCase
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
     * @var JwtService JWT generator/decoder for signup flow.
     */
    private JwtService $jwt;

    /**
     * @var CookieManager Cookie manager for access token handling.
     */
    private CookieManager $cookieManager;

    /**
     * @var SignupService Service under test.
     */
    private SignupService $service;

    /**
     * Setup test environment.
     *
     * Initializes the database connection, recreates the users table,
     * and prepares dependencies for SignupUserService.
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

        waitForDatabase($dbHost, $dbPort);

        $this->pdo = (new Database())->getConnection();
        $this->userQueries = new UserQueries($this->pdo);
        $this->jwt = new JwtService('test-secret-key');

        // Reset table for deterministic state between tests
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

        $this->service = new SignupService(
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
     * Helper to build Request objects.
     *
     * @param array<string, mixed> $body
     * 
     * @return Request
     */
    private function makeRequest(array $body): Request
    {
        return new Request('POST', '/signup', null, null, $body);
    }

    /**
     * Test successful signup and verify:
     * - User is inserted correctly
     * - Password is hashed
     * - Access token cookie is set
     * - JWT contains correct ID
     *
     * @return void
     */
    public function testSignupSuccess(): void
    {
        $req = $this->makeRequest([
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'securePass123',
        ]);

        $this->service->execute($req);

        // Check inserted user
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute(['john_doe']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($user);
        $this->assertSame('john_doe', $user['username']);
        $this->assertSame('john@example.com', $user['email']);
        $this->assertIsString($user['password']);
        $this->assertTrue(password_verify('securePass123', $user['password']));

        // Cookie should be set to reflect login state
        $this->assertSame('access_token', $this->cookieManager->getLastSetCookieName());

        $token = $this->cookieManager->getAccessToken();
        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        // Verify JWT payload correctness
        $payload = $this->jwt->decodeStrict($token);
        $this->assertSame($user['id'], $payload['id']);
    }

    /**
     * Test behavior when trying to sign up using an existing username.
     *
     * @return void
     */
    public function testDuplicateUsernameThrowsException(): void
    {
        // Pre-insert user to force conflict
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute(['1', 'existing', 'existing@example.com', password_hash('x', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest([
            'username' => 'existing',
            'email' => 'new@example.com',
            'password' => 'any',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already exists');

        $this->service->execute($req);
    }

    /**
     * Test duplicate email validation.
     *
     * @return void
     */
    public function testDuplicateEmailThrowsException(): void
    {
        // Force email conflict
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute(['2', 'uniqueuser', 'dup@example.com', password_hash('x', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest([
            'username' => 'newuser',
            'email' => 'dup@example.com',
            'password' => 'password',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already exists');

        $this->service->execute($req);
    }

    /**
     * Test email format validation.
     *
     * @return void
     */
    public function testInvalidEmailThrowsException(): void
    {
        $req = $this->makeRequest([
            'username' => 'userx',
            'email' => 'not-an-email',
            'password' => 'abc123',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->execute($req);
    }

    /**
     * Test missing password handling.
     *
     * @return void
     */
    public function testMissingPasswordThrowsException(): void
    {
        $req = $this->makeRequest([
            'username' => 'abc',
            'email' => 'abc@example.com',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->execute($req);
    }

    /**
     * Verify JWT generated after signup is valid and contains correct fields.
     *
     * @return void
     */
    public function testSignupGeneratesValidJwtToken(): void
    {
        $req = $this->makeRequest([
            'username' => 'jwt_user',
            'email' => 'jwt@example.com',
            'password' => 'abc12345',
        ]);

        $this->service->execute($req);

        $this->assertSame('access_token', $this->cookieManager->getLastSetCookieName());

        $token = $this->cookieManager->getAccessToken();
        $this->assertNotEmpty($token);

        /** @var string $token */
        $payload = $this->jwt->decodeStrict($token);
        $this->assertArrayHasKey('id', $payload);

        // Compare JWT ID with DB ID
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute(['jwt_user']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($user);
        $this->assertSame($user['id'], $payload['id']);
    }

    /**
     * Tests failure where invalid data (e.g., too long username)
     * causes DB-level error and ensures:
     * - User is not persisted
     * - Cookie is not set
     *
     * @return void
     */
    public function testCreateUserWithInvalidDataThrowsRuntimeException(): void
    {
        // Force DB failure with oversized username
        $longUsername = str_repeat('a', 300);
        $req = $this->makeRequest([
            'username' => $longUsername,
            'email' => 'invalid@example.com',
            'password' => 'abc123',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/username.*too long/i');

        try {
            $this->service->execute($req);
        } finally {
            // Ensure user was not inserted after failure
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute(['invalid@example.com']);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertFalse((bool)$user);

            // Cookie must remain unset due to failed signup
            $this->assertNull($this->cookieManager->getAccessToken());
        }
    }
}
