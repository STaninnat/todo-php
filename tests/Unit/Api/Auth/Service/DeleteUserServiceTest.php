<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\DeleteUserService;
use App\DB\UserQueries;
use App\DB\QueryResult;
use App\Utils\CookieManager;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

class DeleteUserServiceTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $userQueries;

    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject */
    private $cookieManager;

    private DeleteUserService $service;

    use ApiTestHelperTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userQueries = $this->createMock(UserQueries::class);
        $this->cookieManager = $this->createMock(CookieManager::class);

        $this->service = new DeleteUserService(
            $this->userQueries,
            $this->cookieManager
        );
    }

    #[DataProvider('userIdProvider')]
    public function testExecuteUserIdValidation(
        ?string $rawUserId,
        ?string $expectedCleanUserId,
        bool $shouldThrow
    ): void {
        if ($shouldThrow) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('User ID is required.');
        } else {
            $this->userQueries
                ->expects($this->once())
                ->method('deleteUser')
                ->with($expectedCleanUserId)
                ->willReturn(QueryResult::ok(null, 1));

            $this->cookieManager
                ->expects($this->once())
                ->method('clearAccessToken');
        }

        $req = $this->makeRequest(['user_id' => $rawUserId]);
        $this->service->execute($req);
    }

    public static function userIdProvider(): array
    {
        return [
            'null'        => [null, null, true],
            'empty'       => ['', '', true],
            'whitespace'  => ['   ', '', true],
            'html tags'   => ['<b>123</b>', '123', false],
        ];
    }

    #[DataProvider('deleteUserResultProvider')]
    public function testExecuteHandlesDeleteUserResult(
        string $userId,
        QueryResult $deleteResult,
        ?string $expectedExceptionMessage,
        bool $shouldClearCookie
    ): void {
        $this->userQueries
            ->expects($this->once())
            ->method('deleteUser')
            ->with($userId)
            ->willReturn($deleteResult);

        if ($expectedExceptionMessage !== null) {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        if ($shouldClearCookie) {
            $this->cookieManager
                ->expects($this->once())
                ->method('clearAccessToken');
        } else {
            $this->cookieManager
                ->expects($this->never())
                ->method('clearAccessToken');
        }

        $req = $this->makeRequest(['user_id' => $userId]);
        $this->service->execute($req);
    }

    public static function deleteUserResultProvider(): array
    {
        return [
            'success' => [
                '123',
                QueryResult::ok(null, 1),
                null,
                true,
            ],
            'fail with error' => [
                '456',
                QueryResult::fail(['DB error']),
                'Failed to delete user: DB error',
                false,
            ],
            'fail with null error' => [
                '789',
                QueryResult::fail(null),
                'Failed to delete user: No changes were made.',
                false,
            ],
        ];
    }
}
