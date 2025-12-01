<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\GetUserService;
use App\DB\QueryResult;
use App\DB\UserQueries;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class GetUserServiceTest
 *
 * Unit tests for the GetUserService class.
 *
 * Covers validation and behavior for fetching user data:
 * - Missing or invalid `user_id` in request
 * - Database query failure scenarios
 * - No user found cases
 * - Successful fetch with valid data
 *
 * Uses data providers to systematically test variations.
 *
 * @package Tests\Unit\Api\Auth\Service
 */
class GetUserServiceUnitTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject Mocked user queries dependency */
    private $userQueries;

    /** @var GetUserService Service under test */
    private GetUserService $service;

    use ApiTestHelperTrait;

    /**
     * Initializes mock dependencies and the service before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock for UserQueries dependency
        $this->userQueries = $this->createMock(UserQueries::class);

        // Inject the mock into the service under test
        $this->service = new GetUserService($this->userQueries);
    }



    /**
     * Provides scenarios where the database query fails.
     *
     * @return array<string, array{0:QueryResult,1:string}>
     */
    public static function queryFailProvider(): array
    {
        return [
            'fail with error info' => [
                QueryResult::fail(['SQLSTATE[HY000]', 'Some error']),
                'Failed to fetch user: SQLSTATE[HY000] | Some error'
            ],
            'fail without error' => [
                QueryResult::fail(null),
                'Failed to fetch user: Unknown database error.'
            ],
        ];
    }

    /**
     * Test that execute() throws RuntimeException when the
     * query fails due to a database error.
     *
     * @param QueryResult $result          Simulated query result.
     * @param string      $expectedMessage Expected exception message.
     *
     * @return void
     */
    #[DataProvider('queryFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenQueryFails(QueryResult $result, string $expectedMessage): void
    {
        // Mock getUserById() to return a failing result
        $this->userQueries
            ->method('getUserById')
            ->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        // Execute service with valid request body and auth
        $req = $this->makeRequest([], [], [], 'POST', '/', ['id' => '123']);
        $this->service->execute($req);
    }

    /**
     * Test that execute() throws RuntimeException when no user
     * is found (affected rows = 0).
     *
     * @return void
     */
    public function testExecuteThrowsRuntimeExceptionWhenUserNotFound(): void
    {
        // Simulate successful query but with no matching user
        $this->userQueries
            ->method('getUserById')
            ->willReturn(QueryResult::ok(null, 0));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch user: No data or changes found.');

        $req = $this->makeRequest([], [], [], 'POST', '/', ['id' => '123']);
        $this->service->execute($req);
    }

    /**
     * Test that execute() returns valid user data when query succeeds.
     *
     * @return void
     */
    public function testExecuteReturnsUserDataWhenSuccessful(): void
    {
        // Simulate valid user record returned from database
        $userData = [
            'username' => 'john_doe',
            'email' => 'john@example.com'
        ];

        $this->userQueries
            ->method('getUserById')
            ->willReturn(QueryResult::ok($userData, 1));

        // Create valid request with auth
        $req = $this->makeRequest([], [], [], 'POST', '/', ['id' => '123']);

        // Execute service and get result
        $result = $this->service->execute($req);

        // Assert returned data matches expected
        $this->assertSame([
            'username' => 'john_doe',
            'email' => 'john@example.com',
        ], $result);
    }
}
