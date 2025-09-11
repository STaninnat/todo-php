<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\GetUserService;
use App\DB\QueryResult;
use App\DB\UserQueries;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class GetUserServiceTest
 *
 * Unit tests for GetUserService.
 *
 * This test suite verifies:
 * - Validation of user_id input
 * - Handling of failed database queries
 * - Handling of not-found users
 * - Successful retrieval of user data
 *
 * @package Tests\Unit\Api\Auth\Service
 */
class GetUserServiceTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $userQueries;

    private GetUserService $service;

    /**
     * Setup mocks and service instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->userQueries = $this->createMock(UserQueries::class);
        $this->service = new GetUserService($this->userQueries);
    }

    /**
     * Data provider for invalid user_id input.
     *
     * @return array<string, array>
     */
    public static function userIdProvider(): array
    {
        return [
            'missing user_id'   => [[]],
            'empty string'      => [['user_id' => '']],
            'only whitespace'   => [['user_id' => '   ']],
        ];
    }

    /**
     * Test that execute() throws InvalidArgumentException for invalid user_id.
     *
     * @param array $input
     *
     * @return void
     */
    #[DataProvider('userIdProvider')]
    public function testExecuteThrowsInvalidArgumentException(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Act: call service with invalid input
        $this->service->execute($input);
    }

    /**
     * Data provider for query failure scenarios.
     *
     * @return array<string, array>
     */
    public static function queryFailProvider(): array
    {
        return [
            'fail with error info' => [
                QueryResult::fail(['SQLSTATE[HY000]', 'Some error']),
                'Failed to fetch user: SQLSTATE[HY000] | Some error'
            ],
            'fail without error'   => [
                QueryResult::fail(null),
                'Failed to fetch user: Unknown error'
            ],
        ];
    }

    /**
     * Test that execute() throws RuntimeException when query fails.
     *
     * @param QueryResult $result
     * @param string      $expectedMessage
     *
     * @return void
     */
    #[DataProvider('queryFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenQueryFails(QueryResult $result, string $expectedMessage): void
    {
        // Mock DB call to return failure
        $this->userQueries
            ->method('getUserById')
            ->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        // Act: call service with valid user_id
        $this->service->execute(['user_id' => '123']);
    }

    /**
     * Test that execute() throws RuntimeException when no user is found.
     *
     * @return void
     */
    public function testExecuteThrowsRuntimeExceptionWhenUserNotFound(): void
    {
        // Mock DB call to return ok result but with zero rows
        $this->userQueries
            ->method('getUserById')
            ->willReturn(QueryResult::ok(null, 0));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User not found.');

        // Act
        $this->service->execute(['user_id' => '123']);
    }

    /**
     * Test that execute() returns correct user data on success.
     *
     * @return void
     */
    public function testExecuteReturnsUserDataWhenSuccessful(): void
    {
        $userData = [
            'username' => 'john_doe',
            'email' => 'john@example.com'
        ];

        // Mock DB call to return successful result with user data
        $this->userQueries
            ->method('getUserById')
            ->willReturn(QueryResult::ok($userData, 1));

        // Act
        $result = $this->service->execute(['user_id' => '123']);

        // Assert: returned data matches expected
        $this->assertSame([
            'username' => 'john_doe',
            'email' => 'john@example.com',
        ], $result);
    }
}
