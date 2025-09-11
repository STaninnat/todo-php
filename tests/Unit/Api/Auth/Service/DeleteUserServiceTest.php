<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\DeleteUserService;
use App\DB\UserQueries;
use App\DB\QueryResult;
use App\Utils\CookieManager;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class DeleteUserServiceTest
 *
 * Unit tests for DeleteUserService.
 *
 * This test suite verifies:
 * - Validation of user_id input (null, empty, whitespace, HTML tags)
 * - Proper handling of deleteUser results (success/fail)
 * - Clearing of access token via CookieManager when deletion succeeds
 *
 * @package Tests\Unit\Api\Auth\Service
 */
class DeleteUserServiceTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $userQueriesMock;

    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject */
    private $cookieManagerMock;

    private DeleteUserService $service;

    /**
     * Setup mocks and service instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects for dependencies
        $this->userQueriesMock = $this->createMock(UserQueries::class);
        $this->cookieManagerMock = $this->createMock(CookieManager::class);

        // Instantiate the service with mocked dependencies
        $this->service = new DeleteUserService(
            $this->userQueriesMock,
            $this->cookieManagerMock
        );
    }

    /**
     * Test that execute() validates user_id correctly.
     *
     * @param ?string $rawUserId          Raw input user_id
     * @param ?string $expectedCleanUserId Expected sanitized user_id
     * @param bool    $shouldThrow        Whether InvalidArgumentException is expected
     *
     * @return void
     */
    #[DataProvider('userIdProvider')]
    public function testExecuteUserIdValidation(?string $rawUserId, ?string $expectedCleanUserId, bool $shouldThrow): void
    {
        if ($shouldThrow) {
            // Expect an InvalidArgumentException for invalid user_id
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('User ID is required.');
        } else {
            // Expect deleteUser to be called with sanitized user_id
            $this->userQueriesMock
                ->expects($this->once())
                ->method('deleteUser')
                ->with($expectedCleanUserId)
                ->willReturn(QueryResult::ok());

            // Expect access token to be cleared after successful deletion
            $this->cookieManagerMock
                ->expects($this->once())
                ->method('clearAccessToken');
        }

        // Execute the service with input
        $this->service->execute(['user_id' => $rawUserId]);
    }

    /**
     * Data provider for testExecuteUserIdValidation().
     *
     * @return array<string, array>
     */
    public static function userIdProvider(): array
    {
        return [
            'null' => [null, null, true],
            'empty string' => ['', '', true],
            'whitespace' => ['   ', '', true],
            'HTML tags' => ['<b>123</b>', '123', false],
        ];
    }

    /**
     * Test that execute() handles deleteUser result correctly.
     *
     * @param string       $userId
     * @param QueryResult  $deleteResult
     * @param ?string      $expectedExceptionMessage
     * @param bool         $shouldClearCookie
     *
     * @return void
     */
    #[DataProvider('deleteUserResultProvider')]
    public function testExecuteHandlesDeleteUserResult(
        string $userId,
        QueryResult $deleteResult,
        ?string $expectedExceptionMessage,
        bool $shouldClearCookie
    ): void {
        // Mock deleteUser to return specified result
        $this->userQueriesMock
            ->expects($this->once())
            ->method('deleteUser')
            ->with($userId)
            ->willReturn($deleteResult);

        if ($expectedExceptionMessage !== null) {
            // Expect RuntimeException if deletion fails
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        if ($shouldClearCookie) {
            // If success -> access token should be cleared
            $this->cookieManagerMock
                ->expects($this->once())
                ->method('clearAccessToken');
        } else {
            // If fail -> access token should not be cleared
            $this->cookieManagerMock
                ->expects($this->never())
                ->method('clearAccessToken');
        }

        // Execute the service
        $this->service->execute(['user_id' => $userId]);
    }

    /**
     * Data provider for testExecuteHandlesDeleteUserResult().
     *
     * @return array<string, array>
     */
    public static function deleteUserResultProvider(): array
    {
        return [
            'success' => [
                '123',
                QueryResult::ok(),
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
                'Failed to delete user: Unknown error',
                false,
            ],
        ];
    }
}
