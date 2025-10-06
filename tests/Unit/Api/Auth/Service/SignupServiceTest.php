<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\SignupService;
use App\DB\QueryResult;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

class SignupServiceTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private UserQueries $userQueries;

    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject */
    private CookieManager $cookieManager;

    /** @var JwtService&\PHPUnit\Framework\MockObject\MockObject */
    private JwtService $jwt;

    private SignupService $service;

    use ApiTestHelperTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userQueries = $this->createMock(UserQueries::class);
        $this->cookieManager = $this->createMock(CookieManager::class);
        $this->jwt = $this->createMock(JwtService::class);

        $this->service = new SignupService(
            $this->userQueries,
            $this->cookieManager,
            $this->jwt
        );
    }

    public static function invalidInputProvider(): array
    {
        return [
            'missing username'  => [[]],
            'empty username'    => [['username' => '', 'email' => 'john@example.com', 'password' => 'pass']],
            'missing email'     => [['username' => 'john', 'password' => 'pass']],
            'empty email'       => [['username' => 'john', 'email' => '', 'password' => 'pass']],
            'invalid email'     => [['username' => 'john', 'email' => 'not-an-email', 'password' => 'pass']],
            'missing password'  => [['username' => 'john', 'email' => 'john@example.com']],
            'empty password'    => [['username' => 'john', 'email' => 'john@example.com', 'password' => '']],
        ];
    }

    #[DataProvider('invalidInputProvider')]
    public function testExecuteThrowsInvalidArgumentException(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest($input);
        $this->service->execute($req);
    }

    public static function checkUserExistsFailProvider(): array
    {
        return [
            'with error info' => [
                QueryResult::fail(['SQLSTATE[HY000]', 'Some error']),
                'Failed to check user existence: SQLSTATE[HY000] | Some error'
            ],
            'without error' => [
                QueryResult::fail(null),
                'Failed to check user existence: No changes were made.'
            ],
        ];
    }

    #[DataProvider('checkUserExistsFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenCheckUserExistsFails(QueryResult $result, string $expectedMessage): void
    {
        $this->userQueries->method('checkUserExists')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
        $this->service->execute($req);
    }

    public function testExecuteThrowsInvalidArgumentExceptionWhenUserExists(): void
    {
        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(true, 1));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Username or email already exists.');

        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
        $this->service->execute($req);
    }

    public static function createUserFailProvider(): array
    {
        return [
            'fail with error info' => [
                QueryResult::fail(['SQLSTATE[HY000]', 'Insert error']),
                'Failed to check user existence: No changes were made.'
            ],
            'fail without error' => [
                QueryResult::fail(null),
                'Failed to check user existence: No changes were made.'
            ],
        ];
    }

    #[DataProvider('createUserFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenCreateUserFails(QueryResult $result, string $expectedMessage): void
    {
        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 0));
        $this->userQueries->method('createUser')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
        $this->service->execute($req);
    }

    public function testExecuteThrowsRuntimeExceptionWhenCreateUserHasNoChanges(): void
    {
        $userData = ['id' => 'uuid', 'username' => 'john', 'email' => 'john@example.com'];
        $result = QueryResult::ok($userData, 0);

        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 0));
        $this->userQueries->method('createUser')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to check user existence: No changes were made.');

        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
        $this->service->execute($req);
    }

    public function testExecuteCallsJwtAndCookieManagerWhenSuccessful(): void
    {
        $userData = ['id' => 'uuid', 'username' => 'john', 'email' => 'john@example.com'];
        $result = QueryResult::ok($userData, 1);

        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 1));
        $this->userQueries->method('createUser')->willReturn($result);

        $this->jwt->expects($this->once())
            ->method('create')
            ->with(['id' => 'uuid'])
            ->willReturn('mock-token');

        $this->cookieManager->expects($this->once())
            ->method('setAccessToken')
            ->with('mock-token', $this->anything());

        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
        $this->service->execute($req);
    }
}
