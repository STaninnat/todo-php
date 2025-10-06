<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\SigninService;
use App\DB\QueryResult;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

class SigninServiceTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private UserQueries $userQueries;

    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject */
    private CookieManager $cookieManager;

    /** @var JwtService&\PHPUnit\Framework\MockObject\MockObject */
    private JwtService $jwt;

    private SigninService $service;

    use ApiTestHelperTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userQueries = $this->createMock(UserQueries::class);
        $this->cookieManager = $this->createMock(CookieManager::class);
        $this->jwt = $this->createMock(JwtService::class);

        $this->service = new SigninService(
            $this->userQueries,
            $this->cookieManager,
            $this->jwt
        );
    }

    public static function invalidInputProvider(): array
    {
        return [
            'missing username' => [['password' => 'pass']],
            'empty username'   => [['username' => '', 'password' => 'pass']],
            'missing password' => [['username' => 'john']],
            'empty password'   => [['username' => 'john', 'password' => '']],
        ];
    }

    #[DataProvider('invalidInputProvider')]
    public function testExecuteThrowsInvalidArgumentExceptionForInvalidInput(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest($input);
        $this->service->execute($req);
    }

    public static function getUserByNameFailProvider(): array
    {
        return [
            'with error info' => [
                QueryResult::fail(['SQLSTATE[HY000]', 'Some error']),
                'Failed to fetch user: SQLSTATE[HY000] | Some error'
            ],
            'without error' => [
                QueryResult::fail(null),
                'Failed to fetch user: No changes were made.'
            ],
        ];
    }

    #[DataProvider('getUserByNameFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenGetUserByNameFails(QueryResult $result, string $expectedMessage): void
    {
        $this->userQueries->method('getUserByName')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $req = $this->makeRequest(['username' => 'john', 'password' => 'pass']);
        $this->service->execute($req);
    }

    public function testExecuteThrowsInvalidArgumentExceptionWhenUserNotFound(): void
    {
        $this->userQueries->method('getUserByName')->willReturn(QueryResult::ok(null, 0));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch user: No changes were made.');

        $req = $this->makeRequest(['username' => 'john', 'password' => 'pass']);
        $this->service->execute($req);
    }

    public function testExecuteThrowsInvalidArgumentExceptionForWrongPassword(): void
    {
        $user = ['id' => 'uuid', 'username' => 'john', 'password' => password_hash('correct-pass', PASSWORD_DEFAULT)];
        $this->userQueries->method('getUserByName')->willReturn(QueryResult::ok($user, 1));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid username or password.');

        $req = $this->makeRequest(['username' => 'john', 'password' => 'wrong-pass']);
        $this->service->execute($req);
    }

    public function testExecuteCallsJwtAndCookieManagerWhenSuccessful(): void
    {
        $user = ['id' => 'uuid', 'username' => 'john', 'password' => password_hash('pass123', PASSWORD_DEFAULT)];
        $this->userQueries->method('getUserByName')->willReturn(QueryResult::ok($user, 1));

        $this->jwt->expects($this->once())
            ->method('create')
            ->with(['id' => 'uuid'])
            ->willReturn('mock-token');

        $this->cookieManager->expects($this->once())
            ->method('setAccessToken')
            ->with('mock-token', $this->anything());

        $req = $this->makeRequest(['username' => 'john', 'password' => 'pass123']);
        $this->service->execute($req);
    }
}
