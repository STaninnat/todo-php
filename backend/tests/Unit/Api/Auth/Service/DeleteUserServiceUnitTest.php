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

/**
 * Class DeleteUserServiceTest
 *
 * Unit tests for the DeleteUserService class.
 *
 * This test suite verifies:
 * - Proper validation of user ID input (including sanitization)
 * - Correct interaction with UserQueries::deleteUser()
 * - Proper cookie clearing behavior upon successful deletion
 * - Handling of various delete result outcomes and exceptions
 *
 * Uses data providers for systematic coverage of scenarios.
 *
 * @package Tests\Unit\Api\Auth\Service
 */
class DeleteUserServiceUnitTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject Mocked database query handler */
    private $userQueries;

    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject Mocked cookie manager */
    private $cookieManager;

    /** @var DeleteUserService Service under test */
    private DeleteUserService $service;

    use ApiTestHelperTrait;

    /**
     * Setup mock dependencies before each test case.
     *
     * Creates mock instances of UserQueries and CookieManager,
     * then initializes DeleteUserService with those mocks.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->userQueries = $this->createMock(UserQueries::class);
        $this->cookieManager = $this->createMock(CookieManager::class);

        // Instantiate service with mocks
        $this->service = new DeleteUserService(
            $this->userQueries,
            $this->cookieManager
        );
    }



    /**
     * Test handling of deleteUser() result outcomes.
     *
     * Ensures that:
     * - Successful deletions clear cookies
     * - Failures throw appropriate exceptions
     * - Error messages are correctly derived from QueryResult
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
        // Expect deleteUser() to be called once with provided ID
        $this->userQueries
            ->expects($this->once())
            ->method('deleteUser')
            ->with($userId)
            ->willReturn($deleteResult);

        // Expect exception if deletion failed
        if ($expectedExceptionMessage !== null) {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        // Expect cookie clearing only on success
        if ($shouldClearCookie) {
            $this->cookieManager
                ->expects($this->once())
                ->method('clearAccessToken');
        } else {
            $this->cookieManager
                ->expects($this->never())
                ->method('clearAccessToken');
        }

        // Build request and execute deletion
        $req = $this->makeRequest([], [], [], 'POST', '/', ['id' => $userId]);
        $this->service->execute($req);
    }

    /**
     * Provides simulated results for deleteUser() to test handling logic.
     *
     * Covers:
     * - Successful deletion
     * - Failure with error messages
     * - Failure with null error messages (default message case)
     *
     * @return array<string, array{0:string,1:QueryResult,2:?string,3:bool}>
     */
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
                'Failed to delete user: Unknown database error.',
                false,
            ],
        ];
    }
}
