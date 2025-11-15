<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Auth\Controller;

use App\Api\Request;
use App\Api\Auth\Controller\UserController;
use App\Api\Auth\Service\SignupService;
use App\Api\Auth\Service\SigninService;
use App\Api\Auth\Service\SignoutService;
use App\Api\Auth\Service\DeleteUserService;
use App\Api\Auth\Service\GetUserService;
use App\Api\Auth\Service\UpdateUserService;
use App\DB\Database;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use Tests\Integration\Api\Helper\TestCookieStorage;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use InvalidArgumentException;

require_once __DIR__ . '/../../../bootstrap_db.php';

class UserControllerIntegrationTest extends TestCase
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
    private UserController $controller;

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

        $this->pdo->exec('DROP TABLE IF EXISTS users');
        $this->pdo->exec("
            CREATE TABLE users (
                id VARCHAR(64) PRIMARY KEY,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $storage = new TestCookieStorage();
        $this->cookieManager = new CookieManager($storage);

        $this->controller = new UserController(
            new DeleteUserService($this->userQueries, $this->cookieManager),
            new GetUserService($this->userQueries),
            new SigninService($this->userQueries, $this->cookieManager, $this->jwt),
            new SignoutService($this->cookieManager),
            new SignupService($this->userQueries, $this->cookieManager, $this->jwt),
            new UpdateUserService($this->userQueries)
        );
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS users');
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $body Request body data
     * 
     * @return Request
     */
    private function makeRequest(string $method, string $path, ?array $body = null): Request
    {
        return new Request($method, $path, null, null, $body);
    }

    public function testSignupUserSuccess(): void
    {
        $req = $this->makeRequest('POST', '/signup', [
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'securePass123',
        ]);

        /** @var array{success: bool, message: string} $res */
        $res = $this->controller->signup($req, true);

        $this->assertTrue($res['success']);
        $this->assertSame('User signup successfully', $res['message']);

        // Verify DB entry
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute(['john_doe']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($user);
        $this->assertSame('john_doe', $user['username']);
        $this->assertSame('john@example.com', $user['email']);
        $this->assertIsString($user['password']);
        $this->assertTrue(password_verify('securePass123', $user['password']));

        // Verify cookie set
        $token = $this->cookieManager->getAccessToken();
        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        $payload = $this->jwt->decodeStrict($token);
        $this->assertSame($user['id'], $payload['id']);
    }

    public function testSignupMissingFieldsThrows(): void
    {
        // Missing username
        $req1 = $this->makeRequest('POST', '/signup', [
            'email' => 'x@example.com',
            'password' => 'abc123',
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $this->controller->signup($req1, true);

        // Missing email
        $req2 = $this->makeRequest('POST', '/signup', [
            'username' => 'userx',
            'password' => 'abc123',
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $this->controller->signup($req2, true);

        // Missing password
        $req3 = $this->makeRequest('POST', '/signup', [
            'username' => 'userx',
            'email' => 'x@example.com',
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $this->controller->signup($req3, true);
    }

    public function testSignupDuplicateEmailThrows(): void
    {
        // Insert existing user
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute(['1', 'existing', 'existing@example.com', password_hash('x', PASSWORD_DEFAULT)]);

        // Duplicate username
        $req1 = $this->makeRequest('POST', '/signup', [
            'username' => 'existing',
            'email' => 'new@example.com',
            'password' => 'abc123',
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username or email already exists.');
        $this->controller->signup($req1, true);

        // Duplicate email
        $req2 = $this->makeRequest('POST', '/signup', [
            'username' => 'newuser',
            'email' => 'existing@example.com',
            'password' => 'abc123',
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username or email already exists.');
        $this->controller->signup($req2, true);
    }

    public function testSignupUsernameTooLongThrows(): void
    {
        $longUsername = str_repeat('a', 300);
        $req = $this->makeRequest('POST', '/signup', [
            'username' => $longUsername,
            'email' => 'long@example.com',
            'password' => 'abc123',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Username too long');
        $this->controller->signup($req, true);
    }

    public function testSigninUserSuccess(): void
    {
        $id = 'u123';
        $username = 'alice';
        $email = 'alice@example.com';
        $hash = password_hash('securePass', PASSWORD_DEFAULT);

        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute([$id, $username, $email, $hash]);

        $req = $this->makeRequest('POST', '/signin', [
            'username' => 'alice',
            'password' => 'securePass',
        ]);

        $res = $this->controller->signin($req, true);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('success', $res);
        $this->assertTrue($res['success']);

        $token = $this->cookieManager->getAccessToken();
        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        $payload = $this->jwt->decodeStrict($token);
        $this->assertSame($id, $payload['id']);
    }

    public function testSigninInvalidPasswordThrows(): void
    {
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute(['id1', 'bob', 'bob@example.com', password_hash('correct', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest('POST', '/signin', [
            'username' => 'bob',
            'password' => 'wrongpass',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid username or password.');
        $this->controller->signin($req, true);
    }

    public function testSigninNonexistentEmailThrows(): void
    {
        $req = $this->makeRequest('POST', '/signin', [
            'username' => 'ghost',
            'password' => 'irrelevant',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid username or password.');
        $this->controller->signin($req, true);
    }

    public function testSignoutUserSuccess(): void
    {
        $this->cookieManager->setAccessToken('dummy_token', time() + 3600);
        $this->assertNotNull($this->cookieManager->getAccessToken());

        $req = $this->makeRequest('POST', '/signout');
        $res = $this->controller->signout($req, true);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('success', $res);
        $this->assertTrue($res['success']);
        $this->assertNull($this->cookieManager->getAccessToken());
    }

    public function testSignoutWhenNoCookieDoesNotError(): void
    {
        $this->cookieManager->clearAccessToken();
        $this->assertNull($this->cookieManager->getAccessToken());

        $req = $this->makeRequest('POST', '/signout');
        $res = $this->controller->signout($req, true);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('success', $res);
        $this->assertTrue($res['success']);
        $this->assertNull($this->cookieManager->getAccessToken());
    }

    public function testGetUserSuccess(): void
    {
        $id = 'u123';
        $username = 'john';
        $email = 'john@example.com';

        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute([$id, $username, $email, password_hash('pass', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest('GET', '/user', ['user_id' => $id]);
        $res = $this->controller->getUser($req, true);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('data', $res);
        $this->assertIsArray($res['data']);
        $this->assertArrayHasKey('username', $res['data']);
        $this->assertArrayHasKey('email', $res['data']);

        $this->assertSame('john', $res['data']['username']);
        $this->assertSame('john@example.com', $res['data']['email']);
    }

    public function testGetUserNotFoundThrows(): void
    {
        $req = $this->makeRequest('GET', '/user', ['user_id' => 'nonexistent']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch user: No data or changes found.');

        $this->controller->getUser($req, true);
    }

    public function testGetUserMissingUserIdThrows(): void
    {
        $req = $this->makeRequest('GET', '/user');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required.');

        $this->controller->getUser($req, true);
    }

    public function testUpdateUserSuccess(): void
    {
        $id = 'u1';
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute([$id, 'john', 'john@example.com', password_hash('pass', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest('PUT', '/user', [
            'user_id' => $id,
            'username' => 'john_new',
            'email' => 'john_new@example.com',
        ]);

        $res = $this->controller->updateUser($req, true);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('data', $res);
        $this->assertIsArray($res['data']);
        $this->assertArrayHasKey('username', $res['data']);
        $this->assertArrayHasKey('email', $res['data']);
        $this->assertSame('john_new', $res['data']['username']);
        $this->assertSame('john_new@example.com', $res['data']['email']);

        $stmt = $this->pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertIsArray($user);
        $this->assertArrayHasKey('username', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertSame('john_new', $user['username']);
        $this->assertSame('john_new@example.com', $user['email']);
    }

    public function testUpdateUserNotFoundThrows(): void
    {
        $req = $this->makeRequest('PUT', '/user', [
            'user_id' => 'nonexistent',
            'username' => 'newname',
            'email' => 'new@example.com',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/update user/i');

        $this->controller->updateUser($req, true);
    }

    public function testUpdateUserInvalidEmailThrows(): void
    {
        $id = 'u1';
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute([$id, 'john', 'john@example.com', password_hash('pass', PASSWORD_DEFAULT)]);

        $req = $this->makeRequest('PUT', '/user', [
            'user_id' => $id,
            'username' => 'john_new',
            'email' => 'invalid-email',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->controller->updateUser($req, true);
    }

    public function testDeleteUserSuccess(): void
    {
        $id = 'u1';
        $this->pdo->prepare("INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)")
            ->execute([$id, 'alice', 'alice@example.com', password_hash('pass', PASSWORD_DEFAULT)]);

        // Simulate cookie set
        $this->cookieManager->setAccessToken('dummy_token', time() + 3600);

        $req = $this->makeRequest('DELETE', '/user', [
            'user_id' => $id,
        ]);

        $this->controller->deleteUser($req, true);

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $this->assertFalse((bool)$stmt->fetch(\PDO::FETCH_ASSOC));

        $this->assertNull($this->cookieManager->getAccessToken());
        $this->assertSame('access_token', $this->cookieManager->getLastSetCookieName());
    }

    public function testDeleteUserNotFoundThrows(): void
    {
        $req = $this->makeRequest('DELETE', '/user', [
            'user_id' => 'nonexistent',
        ]);

        $res = $this->controller->deleteUser($req, true);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('success', $res);
        $this->assertArrayHasKey('message', $res);
        $this->assertTrue($res['success']);
        $this->assertIsString($res['message']);
        $this->assertStringContainsString('deleted', $res['message']);
    }

    public function testFullUserLifecycle(): void
    {
        $assertToken = function (string $expectedUserId) {
            $token = $this->cookieManager->getAccessToken();
            $this->assertNotEmpty($token);
            $this->assertIsString($token);
            $payload = $this->jwt->decodeStrict($token);
            $this->assertSame($expectedUserId, $payload['id']);
        };

        // Signup
        $signupReq = $this->makeRequest('POST', '/signup', [
            'username' => 'lifecycle_user',
            'email' => 'lifecycle@example.com',
            'password' => 'securepass',
        ]);

        $signupRes = $this->controller->signup($signupReq, true);
        $this->assertIsArray($signupRes);
        $this->assertArrayHasKey('success', $signupRes);
        $this->assertTrue($signupRes['success']);

        $token = $this->cookieManager->getAccessToken();
        $this->assertNotNull($token);
        $payload = $this->jwt->decodeStrict($token);
        $this->assertArrayHasKey('id', $payload);
        $userId = $payload['id'];
        $this->assertIsString($userId);

        // Signin
        $signinReq = $this->makeRequest('POST', '/signin', [
            'username' => 'lifecycle_user',
            'password' => 'securepass',
        ]);

        $signinRes = $this->controller->signin($signinReq, true);

        $this->assertIsArray($signinRes);
        $this->assertArrayHasKey('success', $signinRes);
        $this->assertTrue($signinRes['success']);
        $assertToken($userId);

        // Get user
        $getReq = $this->makeRequest('GET', '/user', ['user_id' => $userId]);
        $getRes = $this->controller->getUser($getReq, true);

        $this->assertIsArray($getRes);
        $this->assertArrayHasKey('success', $getRes);
        $this->assertArrayHasKey('data', $getRes);
        $this->assertIsArray($getRes['data']);
        $this->assertArrayHasKey('username', $getRes['data']);
        $this->assertArrayHasKey('email', $getRes['data']);
        $this->assertTrue($getRes['success']);
        $this->assertSame('lifecycle_user', $getRes['data']['username']);
        $this->assertSame('lifecycle@example.com', $getRes['data']['email']);

        // Update user
        $updateReq = $this->makeRequest('PUT', '/user', [
            'user_id' => $userId,
            'username' => 'lifecycle_user_updated',
            'email' => 'updated@example.com',
        ]);

        $updateRes = $this->controller->updateUser($updateReq, true);

        $this->assertIsArray($updateRes);
        $this->assertArrayHasKey('success', $updateRes);
        $this->assertArrayHasKey('data', $updateRes);
        $this->assertIsArray($updateRes['data']);
        $this->assertArrayHasKey('username', $updateRes['data']);
        $this->assertArrayHasKey('email', $updateRes['data']);
        $this->assertTrue($updateRes['success']);
        $this->assertSame('lifecycle_user_updated', $updateRes['data']['username']);
        $this->assertSame('updated@example.com', $updateRes['data']['email']);

        // Delete user
        $deleteReq = $this->makeRequest('DELETE', '/user', ['user_id' => $userId]);
        $deleteRes = $this->controller->deleteUser($deleteReq, true);

        $this->assertIsArray($deleteRes);
        $this->assertArrayHasKey('success', $deleteRes);
        $this->assertArrayHasKey('message', $deleteRes);
        $this->assertTrue($deleteRes['success']);
        $this->assertIsString($deleteRes['message']);
        $this->assertStringContainsString('deleted', $deleteRes['message']);

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertFalse((bool)$user);

        $this->assertNull($this->cookieManager->getAccessToken());

        // Signout (no error if no cookie)
        $signoutReq = $this->makeRequest('POST', '/signout');
        $signoutRes = $this->controller->signout($signoutReq, true);

        $this->assertIsArray($signoutRes);
        $this->assertArrayHasKey('success', $signoutRes);
        $this->assertTrue($signoutRes['success']);
        $this->assertNull($this->cookieManager->getAccessToken());
    }
}
