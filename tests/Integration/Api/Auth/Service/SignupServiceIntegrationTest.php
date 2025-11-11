<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Auth\Service;

use App\Api\Auth\Service\SignupService;
use App\Api\Request;
use App\DB\Database;
use App\DB\QueryResult;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once __DIR__ . '/../../../bootstrap_db.php';

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

    private JwtService $jwt;

    /** @var \PHPUnit\Framework\MockObject\MockObject&CookieManager */
    private $cookieManagerMock;

    private SignupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHost = $_ENV['DB_HOST'] ?? 'db_host';
        assert(is_string($dbHost));

        $dbPort = $_ENV['DB_PORT'] ?? 3306;
        assert(is_numeric($dbPort));
        $dbPort = (int) $dbPort;

        waitForDatabase($dbHost, $dbPort);

        $this->pdo = (new Database())->getConnection();
        $this->userQueries = new UserQueries($this->pdo);
        $this->jwt = new JwtService('test-secret-key');

        // Recreate users table for clean test state
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

        // Mock CookieManager to prevent real cookie operations
        /** @var \PHPUnit\Framework\MockObject\MockObject&CookieManager $mock */
        $mock = $this->createMock(CookieManager::class);
        $mock->method('setAccessToken')
            ->willReturnCallback(function (string $token, int $expiry): void {
                // do nothing by default
            });
        $this->cookieManagerMock = $mock;

        $this->service = new SignupService(
            $this->userQueries,
            $this->cookieManagerMock,
            $this->jwt
        );
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS users');
    }

    public function testSignupSuccess(): void
    {
        $req = new Request('POST', '/signup', null, null, [
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'securePass123',
        ]);

        $this->cookieManagerMock
            ->expects($this->once())
            ->method('setAccessToken')
            ->with(
                $this->callback(fn($token) => is_string($token) && strlen($token) > 10),
                $this->greaterThan(time())
            );

        $this->service->execute($req);

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute(['john_doe']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($user);
        $this->assertSame('john_doe', $user['username']);
        $this->assertSame('john@example.com', $user['email']);
        $this->assertIsString($user['password']);
        $this->assertTrue(password_verify('securePass123', $user['password']));
    }

    public function testDuplicateUsernameThrowsException(): void
    {
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute(['1', 'existing', 'existing@example.com', password_hash('x', PASSWORD_DEFAULT)]);

        $req = new Request('POST', '/signup', null, null, [
            'username' => 'existing',
            'email' => 'new@example.com',
            'password' => 'any',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already exists');

        $this->service->execute($req);
    }

    public function testDuplicateEmailThrowsException(): void
    {
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute(['2', 'uniqueuser', 'dup@example.com', password_hash('x', PASSWORD_DEFAULT)]);

        $req = new Request('POST', '/signup', null, null, [
            'username' => 'newuser',
            'email' => 'dup@example.com',
            'password' => 'password',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already exists');

        $this->service->execute($req);
    }

    public function testInvalidEmailThrowsException(): void
    {
        $req = new Request('POST', '/signup', null, null, [
            'username' => 'userx',
            'email' => 'not-an-email',
            'password' => 'abc123',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->execute($req);
    }

    public function testMissingPasswordThrowsException(): void
    {
        $req = new Request('POST', '/signup', null, null, [
            'username' => 'abc',
            'email' => 'abc@example.com',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->execute($req);
    }

    public function testSignupGeneratesValidJwtToken(): void
    {
        $req = new Request('POST', '/signup', null, null, [
            'username' => 'jwt_user',
            'email' => 'jwt@example.com',
            'password' => 'abc12345',
        ]);

        $capturedToken = '';

        $this->cookieManagerMock
            ->expects($this->once())
            ->method('setAccessToken')
            ->willReturnCallback(function (string $token, int $expiry) use (&$capturedToken) {
                $capturedToken = $token;
                return null;
            });

        $this->service->execute($req);

        $this->assertNotSame('', $capturedToken, 'Expected JWT token to be set.');

        /** @var string $capturedToken */
        $payload = $this->jwt->decodeStrict($capturedToken);

        $this->assertArrayHasKey('id', $payload);

        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute(['jwt_user']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($user);
        $this->assertSame($user['id'], $payload['id']);
    }

    public function testCreateUserReturnsInvalidDataThrowsRuntimeException(): void
    {
        $userQueriesMock = $this->createMock(UserQueries::class);
        $userQueriesMock->method('checkUserExists')->willReturn(QueryResult::ok(false, 1));
        $userQueriesMock->method('createUser')->willReturn(QueryResult::ok(['invalid' => 'structure'], 1));

        $service = new SignupService(
            $userQueriesMock,
            $this->cookieManagerMock,
            $this->jwt
        );

        $req = new Request('POST', '/signup', null, null, [
            'username' => 'broken_user',
            'email' => 'broken@example.com',
            'password' => 'abc123',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid user data returned from createUser.');

        $service->execute($req);
    }
}
