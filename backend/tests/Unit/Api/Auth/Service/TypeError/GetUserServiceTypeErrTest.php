<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use App\Api\Auth\Service\GetUserService;
use App\Api\Request;
use App\DB\UserQueries;
use App\DB\QueryResult;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;

/**
 * Class GetUserServiceTypeErrTest
 *
 * Unit tests for GetUserService focusing on type-safety
 * and error-handling scenarios.
 *
 * Covers:
 * - Passing invalid types to execute()
 * - Invalid or missing user_id
 * - Failures in database query or success flag
 * - Valid successful data retrieval
 *
 * @package Tests\Unit\Api\Auth\Service\TypeError
 */
class GetUserServiceTypeErrTest extends TestCase
{
    /** @var GetUserService Service under test */
    private GetUserService $service;

    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject Mocked UserQueries dependency */
    private UserQueries $userQueries;

    /**
     * Setup the mocked dependencies and instantiate the service.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userQueries = $this->createMock(UserQueries::class);
        $this->service = new GetUserService($this->userQueries);
    }

    /**
     * Provides invalid argument types for execute() method.
     *
     * Each entry simulates calling execute() with a type
     * that is not an instance of Request.
     *
     * @return array<string, array{0:mixed}>
     */
    public static function invalidExecuteArgsProvider(): array
    {
        return [
            'null instead of Request' => [null],
            'int instead of Request' => [123],
            'array instead of Request' => [[]],
            'string instead of Request' => ['not-a-request'],
        ];
    }

    /**
     * Test that execute() throws TypeError when argument is not a Request instance.
     *
     * @param mixed $invalidArg Invalid argument passed to execute().
     *
     * @return void
     */
    #[DataProvider('invalidExecuteArgsProvider')]
    public function testExecuteThrowsTypeErrorWhenNotRequest(mixed $invalidArg): void
    {
        $this->expectException(TypeError::class);

        /** @phpstan-ignore-next-line Deliberately using wrong type to trigger TypeError */
        $this->service->execute($invalidArg);
    }



    /**
     * Test that execute() throws RuntimeException when QueryResult success flag is false.
     *
     * Simulates a situation where the query executes but the result
     * is marked as unsuccessful manually.
     *
     * @return void
     */
    public function testExecuteThrowsRuntimeExceptionWhenEnsureSuccessFails(): void
    {
        $req = new Request();
        $req->auth = ['id' => '123'];

        // Simulate a result that looks OK but marked as failed
        $result = QueryResult::ok(['username' => 'u', 'email' => 'e'], 1);
        $result->success = false; // Force failure manually

        // Mock getUserById() to return failed QueryResult
        $this->userQueries
            /** @phpstan-ignore-next-line Simulated return type */
            ->method('getUserById')
            ->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch user');

        $this->service->execute($req);
    }

    /**
     * Test that execute() throws RuntimeException when no user is found.
     *
     * @return void
     */
    public function testExecuteThrowsRuntimeExceptionWhenUserNotFound(): void
    {
        $req = new Request();
        $req->auth = ['id' => '123'];

        // Simulate a valid query but no rows found
        $result = QueryResult::ok(null, 0);

        $this->userQueries
            /** @phpstan-ignore-next-line Mocked method return */
            ->method('getUserById')
            ->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch user: No data or changes found.');

        $this->service->execute($req);
    }

    /**
     * Test that execute() returns user data array when successful.
     *
     * @return void
     */
    public function testExecuteReturnsUserArrayOnSuccess(): void
    {
        $req = new Request();
        $req->auth = ['id' => '123'];

        // Simulate successful query result
        $result = QueryResult::ok(
            ['username' => 'john', 'email' => 'john@example.com'],
            1
        );

        $this->userQueries
            /** @phpstan-ignore-next-line Mocked method return */
            ->method('getUserById')
            ->willReturn($result);

        $output = $this->service->execute($req);

        // Assert the returned array matches expected user data
        $this->assertSame(
            ['username' => 'john', 'email' => 'john@example.com'],
            $output
        );
    }
}
