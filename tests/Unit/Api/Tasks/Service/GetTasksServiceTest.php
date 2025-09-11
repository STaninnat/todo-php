<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use App\Api\Tasks\Service\GetTasksService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class GetTasksServiceTest
 *
 * Unit tests for GetTasksService.
 *
 * This test suite verifies:
 * - Validation for required user_id field
 * - Handling of failed database queries
 * - Handling of empty task results
 * - Correct return structure with tasks and total pages
 *
 * @package Tests\Unit\Api\Tasks\Service
 */
class GetTasksServiceTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    private GetTasksService $service;

    /**
     * Setup mocks and service instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Mock TaskQueries to isolate service logic
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new GetTasksService($this->taskQueries);
    }

    /**
     * Test: execute() throws InvalidArgumentException if user_id is missing.
     *
     * @return void
     */
    public function testMissingUserIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Call service without 'user_id'
        $this->service->execute([]);
    }

    /**
     * Test: execute() throws RuntimeException if getTasksByUserID() fails.
     *
     * @return void
     */
    public function testGetTasksFailsThrowsRuntimeException(): void
    {
        // Mock getTasksByUserID to return failure
        $this->taskQueries->method('getTasksByUserID')
            ->willReturn(QueryResult::fail(['DB error']));

        $this->expectException(RuntimeException::class);

        $this->service->execute(['user_id' => '123']);
    }

    /**
     * Test: execute() throws RuntimeException if getTasksByUserID() returns no data.
     *
     * @return void
     */
    public function testGetTasksReturnsNoDataThrowsRuntimeException(): void
    {
        // Mock getTasksByUserID to return empty array
        $this->taskQueries->method('getTasksByUserID')
            ->willReturn(QueryResult::ok([]));

        $this->expectException(RuntimeException::class);

        $this->service->execute(['user_id' => '123']);
    }

    /**
     * Test: execute() returns expected tasks and total pages on success.
     *
     * @return void
     */
    public function testGetTasksSuccessReturnsExpectedArray(): void
    {
        $tasks = [
            ['id' => 1, 'title' => 'Test Task'],
            ['id' => 2, 'title' => 'Another Task'],
        ];

        // Mock getTasksByUserID to return successful task array
        $this->taskQueries->method('getTasksByUserID')
            ->willReturn(QueryResult::ok($tasks, count($tasks)));

        // Mock getTotalTasks to calculate total pages
        $this->taskQueries->method('getTotalTasks')
            ->willReturn(QueryResult::ok(20));

        $result = $this->service->execute(['user_id' => '123']);

        // Assert structure and values
        $this->assertIsArray($result);
        $this->assertSame($tasks, $result['task']);
        $this->assertSame(2, count($result['task']));
        $this->assertSame(2, $result['totalPages']); // ceil(20 / 10)
    }
}
