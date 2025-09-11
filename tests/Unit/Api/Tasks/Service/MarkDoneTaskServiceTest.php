<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use App\Api\Tasks\Service\MarkDoneTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class MarkDoneTaskServiceTest
 *
 * Unit tests for MarkDoneTaskService.
 *
 * This test suite verifies:
 * - Validation for required fields (id, user_id, is_done)
 * - Handling of invalid task IDs and status values
 * - Handling of failed database operations (getTaskByID, markDone)
 * - Correct return structure with updated task and total pages
 *
 * @package Tests\Unit\Api\Tasks\Service
 */
class MarkDoneTaskServiceTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    private MarkDoneTaskService $service;

    /**
     * Setup mocks and service instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Mock TaskQueries to isolate service logic
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new MarkDoneTaskService($this->taskQueries);
    }

    /**
     * Test: execute() throws InvalidArgumentException if id or user_id is missing.
     *
     * @return void
     */
    public function testMissingIdOrUserIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Call service with missing 'id'
        $this->service->execute(['user_id' => '123']);
    }

    /**
     * Test: execute() throws InvalidArgumentException if is_done value is invalid.
     *
     * @return void
     */
    public function testInvalidStatusValueThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // is_done should be 0 or 1
        $this->service->execute(['id' => '1', 'user_id' => '123', 'is_done' => 2]);
    }

    /**
     * Test: execute() throws InvalidArgumentException if task id is non-numeric.
     *
     * @return void
     */
    public function testNonNumericTaskIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->execute(['id' => 'abc', 'user_id' => '123', 'is_done' => 1]);
    }

    /**
     * Test: execute() throws RuntimeException if getTaskByID() fails.
     *
     * @return void
     */
    public function testGetTaskByIdFailsThrowsRuntimeException(): void
    {
        // Mock getTaskByID to fail
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::fail(['DB error']));

        $this->expectException(RuntimeException::class);

        $this->service->execute(['id' => '1', 'user_id' => '123', 'is_done' => 1]);
    }

    /**
     * Test: execute() throws RuntimeException if getTaskByID() returns null.
     *
     * @return void
     */
    public function testGetTaskByIdReturnsNullThrowsRuntimeException(): void
    {
        // Mock getTaskByID to return no data
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok(null, 0));

        $this->expectException(RuntimeException::class);

        $this->service->execute(['id' => '1', 'user_id' => '123', 'is_done' => 1]);
    }

    /**
     * Test: execute() throws RuntimeException if markDone() fails.
     *
     * @return void
     */
    public function testMarkDoneFailsThrowsRuntimeException(): void
    {
        // Mock getTaskByID to succeed
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok(['id' => 1, 'title' => 'Task'], 1));

        // Mock markDone to fail
        $this->taskQueries->method('markDone')
            ->willReturn(QueryResult::fail(['Update error']));

        $this->expectException(RuntimeException::class);

        $this->service->execute(['id' => '1', 'user_id' => '123', 'is_done' => 1]);
    }

    /**
     * Test: execute() throws RuntimeException if markDone() affected 0 rows.
     *
     * @return void
     */
    public function testMarkDoneNotChangedThrowsRuntimeException(): void
    {
        // Mock getTaskByID to succeed
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok(['id' => 1, 'title' => 'Task'], 1));

        // Mock markDone to succeed but affect 0 rows
        $this->taskQueries->method('markDone')
            ->willReturn(QueryResult::ok(['id' => 1, 'title' => 'Task'], 0));

        $this->expectException(RuntimeException::class);

        $this->service->execute(['id' => '1', 'user_id' => '123', 'is_done' => 1]);
    }

    /**
     * Test: execute() returns expected task and total pages on success.
     *
     * @return void
     */
    public function testMarkDoneSuccessReturnsExpectedArray(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'is_done' => 1];

        // Mock getTaskByID to return the task
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        // Mock markDone to return the updated task
        $this->taskQueries->method('markDone')
            ->willReturn(QueryResult::ok($task, 1));

        // Mock getTotalTasks to calculate total pages
        $this->taskQueries->method('getTotalTasks')
            ->willReturn(QueryResult::ok(15));

        $result = $this->service->execute(['id' => '1', 'user_id' => '123', 'is_done' => 1]);

        // Assert structure and values
        $this->assertIsArray($result);
        $this->assertSame($task, $result['task']);
        $this->assertSame(2, $result['totalPages']); // ceil(15 / 10)
    }
}
