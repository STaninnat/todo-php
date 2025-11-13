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

    private JwtService $jwt;

    private CookieManager $cookieManager;

    private SigninService $service;

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

        // Reset table
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
        return new Request('POST', '/signin', null, null, $body);
    }

    public function testSigninSuccess(): void
    {
        $id = 'u123';
        $username = 'johnny';
        $email = 'johnny@example.com';
        $hash = password_hash('secure123', PASSWORD_DEFAULT);

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

        $payload = $this->jwt->decodeStrict($token);
        $this->assertSame($id, $payload['id']);
    }

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

        $this->service->execute($req);
    }

    public function testUnknownUserThrowsException(): void
    {
        $req = $this->makeRequest([
            'username' => 'ghost',
            'password' => 'irrelevant',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid username or password.');

        $this->service->execute($req);
    }

    public function testMissingPasswordThrowsException(): void
    {
        $req = $this->makeRequest(['username' => 'someone']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->execute($req);
    }

    public function testMissingUsernameThrowsException(): void
    {
        $req = $this->makeRequest(['password' => 'pass']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->execute($req);
    }

    public function testGenerateValidJwtToken(): void
    {
        $id = 'jwt123';
        $username = 'jwtuser';
        $email = 'jwt@example.com';
        $hash = password_hash('abc12345', PASSWORD_DEFAULT);

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
        $this->assertArrayHasKey('id', $payload);
        $this->assertSame($id, $payload['id']);
    }

    public function testInvalidUserDataThrowsRuntimeException(): void
    {
        $this->pdo->exec("ALTER TABLE users DROP COLUMN password");
        $this->pdo->prepare("INSERT INTO users (id, username, email) VALUES (?, ?, ?)")
            ->execute(['badid', 'broken', 'broken@example.com']);

        $req = $this->makeRequest([
            'username' => 'broken',
            'password' => 'pass',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid user data returned from getUserByName.');

        $this->service->execute($req);
    }

    public function testDatabaseFailThrowsRuntimeException(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS users');

        $req = $this->makeRequest([
            'username' => 'any',
            'password' => 'x',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/fetch user/i');

        $this->service->execute($req);
    }
}
