<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\UpdateUserService;
use App\DB\QueryResult;
use App\DB\UserQueries;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class UpdateUserServiceTest
 *
 * Unit tests for UpdateUserService.
 *
 * This test suite covers:
 * - Validation of input (user_id, username, email)
 * - Handling DB query failures in checkUserExists and updateUser
 * - Handling of username/email collisions
 * - Successful update returning updated user data
 */
class UpdateUserServiceTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $userQueries;

    private UpdateUserService $service;

    /**
     * Setup mocks and service instance before each test.
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->userQueries = $this->createMock(UserQueries::class);
        $this->service = new UpdateUserService($this->userQueries);
    }

    /**
     * Data provider for invalid input scenarios.
     * 
     * @return array<string, array>
     */
    public static function invalidInputProvider(): array
    {
        return [
            'missing user_id'  => [[]],
            'empty user_id'    => [['user_id' => '']],
            'whitespace id'    => [['user_id' => '   ']],
            'missing username' => [['user_id' => '1']],
            'empty username'   => [['user_id' => '1', 'username' => '']],
            'missing email'    => [['user_id' => '1', 'username' => 'john']],
            'empty email'      => [['user_id' => '1', 'username' => 'john', 'email' => '']],
            'invalid email'    => [['user_id' => '1', 'username' => 'john', 'email' => 'not-an-email']],
        ];
    }

    /**
     * Test that execute() throws InvalidArgumentException for invalid input.
     * 
     * @param array $input
     *
     * @return void
     */
    #[DataProvider('invalidInputProvider')]
    public function testExecuteThrowsInvalidArgumentException(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Act: call service with invalid input
        $this->service->execute($input);
    }

    /**
     * Data provider for DB failures in checkUserExists.
     * 
     * @return array<string, array>
     */
    public static function checkUserExistsFailProvider(): array
    {
        return [
            'with error info' => [
                QueryResult::fail(['SQLSTATE[HY000]', 'Some error']),
                'Failed to check user existence: SQLSTATE[HY000] | Some error'
            ],
            'without error'   => [
                QueryResult::fail(null),
                'Failed to check user existence: Unknown error'
            ],
        ];
    }

    /**
     * Test that execute() throws RuntimeException when checkUserExists fails.
     * 
     * @param array $input
     *
     * @return void
     */
    #[DataProvider('checkUserExistsFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenCheckUserExistsFails(QueryResult $result, string $expectedMessage): void
    {
        $this->userQueries->method('checkUserExists')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        // Act
        $this->service->execute(['user_id' => '1', 'username' => 'john', 'email' => 'john@example.com']);
    }

    /**
     * Test that execute() throws RuntimeException if username or email already exists.
     * 
     * @return void
     */
    public function testExecuteThrowsRuntimeExceptionWhenUsernameOrEmailAlreadyExists(): void
    {
        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(true, 1));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Username or email already exists.');

        // Act
        $this->service->execute(['user_id' => '1', 'username' => 'john', 'email' => 'john@example.com']);
    }

    /**
     * Data provider for DB failures in updateUser.
     * 
     * @return array<string, array>
     */
    public static function updateUserFailProvider(): array
    {
        return [
            'fail with error info' => [
                QueryResult::fail(['SQLSTATE[HY000]', 'Update error']),
                'Failed to update user: SQLSTATE[HY000] | Update error'
            ],
            'fail without error' => [
                QueryResult::fail(null),
                'Failed to update user: Unknown error'
            ],
        ];
    }

    /**
     * Test that execute() throws RuntimeException when updateUser fails.
     * 
     * @param array $input
     *
     * @return void
     */
    #[DataProvider('updateUserFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenUpdateUserFails(QueryResult $result, string $expectedMessage): void
    {
        // Mock user not exists check to pass
        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 0));
        // Mock update failure
        $this->userQueries->method('updateUser')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        // Act
        $this->service->execute(['user_id' => '1', 'username' => 'john', 'email' => 'john@example.com']);
    }

    /**
     * Test that execute() throws RuntimeException when update affects 0 rows.
     * 
     * @return void
     */
    public function testExecuteThrowsRuntimeExceptionWhenUpdateUserHasNoChanges(): void
    {
        $userData = ['username' => 'john', 'email' => 'john@example.com'];
        $result = QueryResult::ok($userData, 0); // success but affected = 0

        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 0));
        $this->userQueries->method('updateUser')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to update user: Unknown error');

        // Act
        $this->service->execute(['user_id' => '1', 'username' => 'john', 'email' => 'john@example.com']);
    }

    /**
     * Test that execute() returns updated user data when successful.
     * 
     * @return void
     */
    public function testExecuteReturnsUpdatedUserDataWhenSuccessful(): void
    {
        $userData = ['username' => 'john', 'email' => 'john@example.com'];
        $result = QueryResult::ok($userData, 1);

        // Mock user not exists check to pass
        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 0));
        // Mock successful update
        $this->userQueries->method('updateUser')->willReturn($result);

        // Act
        $output = $this->service->execute(['user_id' => '1', 'username' => 'john', 'email' => 'john@example.com']);

        // Assert
        $this->assertSame($userData, $output);
    }
}
