<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\GetUserService;
use App\Api\Request;
use App\DB\QueryResult;
use App\DB\UserQueries;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

class GetUserServiceTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $userQueries;

    private GetUserService $service;

    use ApiTestHelperTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userQueries = $this->createMock(UserQueries::class);
        $this->service = new GetUserService($this->userQueries);
    }

    public static function userIdProvider(): array
    {
        return [
            'missing user_id'   => [[]],
            'empty string'      => [['user_id' => '']],
            'only whitespace'   => [['user_id' => '   ']],
        ];
    }

    #[DataProvider('userIdProvider')]
    public function testExecuteThrowsInvalidArgumentException(array $body): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest($body);
        $this->service->execute($req);
    }

    public static function queryFailProvider(): array
    {
        return [
            'fail with error info' => [
                QueryResult::fail(['SQLSTATE[HY000]', 'Some error']),
                'Failed to fetch user: SQLSTATE[HY000] | Some error'
            ],
            'fail without error'   => [
                QueryResult::fail(null),
                'Failed to fetch user: No changes were made.'
            ],
        ];
    }

    #[DataProvider('queryFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenQueryFails(QueryResult $result, string $expectedMessage): void
    {
        $this->userQueries
            ->method('getUserById')
            ->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $req = $this->makeRequest(['user_id' => '123']);
        $this->service->execute($req);
    }

    public function testExecuteThrowsRuntimeExceptionWhenUserNotFound(): void
    {
        $this->userQueries
            ->method('getUserById')
            ->willReturn(QueryResult::ok(null, 0));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch user: No changes were made.');

        $req = $this->makeRequest(['user_id' => '123']);
        $this->service->execute($req);
    }

    public function testExecuteReturnsUserDataWhenSuccessful(): void
    {
        $userData = [
            'username' => 'john_doe',
            'email' => 'john@example.com'
        ];

        $this->userQueries
            ->method('getUserById')
            ->willReturn(QueryResult::ok($userData, 1));

        $req = $this->makeRequest(['user_id' => '123']);
        $result = $this->service->execute($req);

        $this->assertSame([
            'username' => 'john_doe',
            'email' => 'john@example.com',
        ], $result);
    }
}
