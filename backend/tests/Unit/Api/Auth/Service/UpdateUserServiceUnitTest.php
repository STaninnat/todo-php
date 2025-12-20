<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\UpdateUserService;
use App\DB\QueryResult;
use App\DB\UserQueries;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class UpdateUserServiceTest
 *
 * Unit tests for UpdateUserService.
 *
 * Covers various scenarios including:
 * - Invalid input parameters
 * - Failures in checking user existence
 * - Username or email conflicts
 * - Failures during user update
 * - Successful updates
 *
 * Uses data providers to test multiple scenarios efficiently.
 *
 * @package Tests\Unit\Api\Auth\Service
 */
class UpdateUserServiceUnitTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject Mocked UserQueries instance */
    private $userQueries;

    /** @var UpdateUserService Service under test */
    private UpdateUserService $service;

    // Include helper for creating Request objects in tests
    use ApiTestHelperTrait;

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
     * Provides invalid input data to test input validation.
     *
     * @return array<string,array<int,array<string,string>>> Array of test cases
     */
    public static function invalidInputProvider(): array
    {
        return [
            'missing username' => [['username' => '']],
            'empty username' => [['username' => '']],
            'missing email' => [['username' => 'john']],
            'empty email' => [['username' => 'john', 'email' => '']],
            'invalid email' => [['username' => 'john', 'email' => 'not-an-email']],
        ];
    }

    /**
     * Test that execute() throws InvalidArgumentException for invalid input.
     *
     * @param array<string,string> $input Input data for request
     *
     * @return void
     */
    #[DataProvider('invalidInputProvider')]
    public function testExecuteThrowsInvalidArgumentException(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest($input, [], [], 'POST', '/', ['id' => '1']);
        $this->service->execute($req);
    }

    /**
     * Provides QueryResult fail cases for checkUserExists().
     *
     * @return array<string,array{0:QueryResult,1:string}>
     */
    public static function checkUserExistsFailProvider(): array
    {
        return [
            'with error info' => [
                QueryResult::fail(['SQLSTATE[HY000]', 'Some error']),
                'Failed to check user existence: SQLSTATE[HY000] | Some error'
            ],
            'without error' => [
                QueryResult::fail(null),
                'Failed to check user existence: Unknown database error.'
            ],
        ];
    }

    /**
     * Test that execute() throws RuntimeException if checkUserExists fails.
     *
     * @param QueryResult $result          Simulated failure result
     * @param string      $expectedMessage Expected exception message
     *
     * @return void
     */
    #[DataProvider('checkUserExistsFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenCheckUserExistsFails(QueryResult $result, string $expectedMessage): void
    {
        $this->userQueries->method('checkUserExistsExclude')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com'], [], [], 'POST', '/', ['id' => '1']);
        $this->service->execute($req);
    }

    /**
     * Test that execute() throws RuntimeException if username or email already exists.
     *
     * @return void
     */
    public function testExecuteThrowsRuntimeExceptionWhenUsernameOrEmailAlreadyExists(): void
    {
        // Simulate user already exists
        $this->userQueries->method('checkUserExistsExclude')->willReturn(QueryResult::ok(true, 1));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Username or email already exists.');

        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com'], [], [], 'POST', '/', ['id' => '1']);
        $this->service->execute($req);
    }

    /**
     * Provides QueryResult fail cases for updateUser().
     *
     * @return array<string,array{0:QueryResult,1:string}>
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
                'Failed to update user: Unknown database error.'
            ],
        ];
    }

    /**
     * Test that execute() throws RuntimeException if updateUser fails.
     *
     * @param QueryResult $result          Simulated failure result
     * @param string      $expectedMessage Expected exception message
     *
     * @return void
     */
    #[DataProvider('updateUserFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenUpdateUserFails(QueryResult $result, string $expectedMessage): void
    {
        // Simulate user does not exist yet
        $this->userQueries->method('checkUserExistsExclude')->willReturn(QueryResult::ok(false, 0));
        $this->userQueries->method('updateUser')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com'], [], [], 'POST', '/', ['id' => '1']);
        $this->service->execute($req);
    }

    /**
     * Test that execute() throws RuntimeException if updateUser returns no changes.
     *
     * @return void
     */
    public function testExecuteThrowsRuntimeExceptionWhenUpdateUserHasNoChanges(): void
    {
        $userData = ['username' => 'john', 'email' => 'john@example.com'];
        $result = QueryResult::ok($userData, 0); // 0 rows affected = no changes

        $this->userQueries->method('checkUserExistsExclude')->willReturn(QueryResult::ok(false, 0));
        $this->userQueries->method('updateUser')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to update user: No data or changes found.');

        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com'], [], [], 'POST', '/', ['id' => '1']);
        $this->service->execute($req);
    }

    /**
     * Test that execute() successfully returns updated user data.
     *
     * @return void
     */
    public function testExecuteReturnsUpdatedUserDataWhenSuccessful(): void
    {
        $userData = ['username' => 'john', 'email' => 'john@example.com'];
        $result = QueryResult::ok($userData, 1); // 1 row affected = update success

        $this->userQueries->method('checkUserExistsExclude')->willReturn(QueryResult::ok(false, 1));
        $this->userQueries->method('updateUser')->willReturn($result);

        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com'], [], [], 'POST', '/', ['id' => '1']);
        $output = $this->service->execute($req);

        $this->assertSame($userData, $output); // Assert returned data matches expected
    }
}
