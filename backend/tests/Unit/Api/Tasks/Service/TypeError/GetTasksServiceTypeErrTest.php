<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service\TypeError;

use App\Api\Request;
use App\Api\Tasks\Service\GetTasksService;
use App\DB\TaskQueries;
use App\DB\QueryResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use RuntimeException;
use TypeError;

/**
 * Class GetTasksServiceTypeErrTest
 *
 * Unit tests for GetTasksService that specifically cover type errors
 * and invalid input scenarios for the user_id field.
 *
 * Scenarios include:
 * - user_id passed in params, query, or body as invalid types
 * - user_id missing or empty
 * - Mocked DB failures during execution
 *
 * Uses a data provider to cover multiple invalid input cases efficiently.
 *
 * @package Tests\Unit\Api\Tasks\Service\TypeError
 */
class GetTasksServiceTypeErrTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject Mocked TaskQueries dependency */
    private TaskQueries $taskQueriesMock;

    /** @var GetTasksService&\PHPUnit\Framework\MockObject\MockObject Service under test */
    private GetTasksService $service;

    /**
     * Set up the test environment.
     *
     * Creates a mocked TaskQueries instance and initializes
     * the GetTasksService with it.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->taskQueriesMock = $this->createMock(TaskQueries::class);
        $this->service = new GetTasksService($this->taskQueriesMock);
    }



    /**
     * Test execution with valid user_id but database mock fails.
     *
     * Ensures that service correctly throws RuntimeException
     * when the underlying TaskQueries returns a failure.
     */
    public function testExecuteWithValidUserIdButMockFails(): void
    {
        $request = new Request();
        $request->auth = ['id' => 'u123'];

        // Mock getTasksByUserID to return a failure
        $this->taskQueriesMock->method('getTasksByUserID')
            ->willReturn(QueryResult::fail(['DB error']));

        $this->expectException(\RuntimeException::class);
        $this->service->execute($request);
    }
}
