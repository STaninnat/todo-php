<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use App\Api\Tasks\Service\UpdateTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class UpdateTaskServiceTest
 *
 * Unit tests for UpdateTaskService.
 *
 * This test suite verifies:
 * - Validation for required fields (id, user_id, title)
 * - Handling of invalid task IDs and status values
 * - Handling of failed database operations (getTaskByID, updateTask)
 * - Correct return structure with updated task and total pages
 *
 * @package Tests\Unit\Api\Tasks\Service
 */
class UpdateTaskServiceTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    private UpdateTaskService $service;

    /**
     * Setup mocks and service instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Mock TaskQueries to isolate service logic
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new UpdateTaskService($this->taskQueries);
    }

    /**
     * Test: execute() throws InvalidArgumentException if id or user_id is missing.
     *
     * @return void
     */
    public function testMissingIdOrUserIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // missing 'id'
        $this->service->execute(['user_id' => '123']);
    }

    /**
     * Test: execute() throws InvalidArgumentException if title is missing.
     *
     * @return void
     */
    public function testMissingTitleThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // missing 'title'
        $this->service->execute(['id' => '1', 'user_id' => '123']);
    }

    /**
     * Test: invalid is_done value defaults to 0.
     *
     * @return void
     */
    public function testInvalidStatusValueDefaultsToZero(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'description' => '', 'is_done' => 0];

        // Mock getTaskByID to return existing task
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        // Mock updateTask to succeed
        $this->taskQueries->method('updateTask')
            ->willReturn(QueryResult::ok($task, 1));

        // Mock getTotalTasks for pagination
        $this->taskQueries->method('getTotalTasks')
            ->willReturn(QueryResult::ok(12));

        $result = $this->service->execute([
            'id' => '1',
            'user_id' => '123',
            'title' => 'Task',
            'description' => '',
            'is_done' => 2 // invalid -> defaults to 0
        ]);

        // Assert result structure
        $this->assertSame($task, $result['task']);
        $this->assertSame(2, $result['totalPages']); // ceil(12/10)
    }

    /**
     * Test: execute() throws InvalidArgumentException if task id is non-numeric.
     *
     * @return void
     */
    public function testNonNumericTaskIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->execute([
            'id' => 'abc',
            'user_id' => '123',
            'title' => 'Task'
        ]);
    }

    /**
     * Test: execute() throws RuntimeException if getTaskByID() fails.
     *
     * @return void
     */
    public function testGetTaskByIdFailsThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::fail(['DB error']));

        $this->expectException(RuntimeException::class);

        $this->service->execute([
            'id' => '1',
            'user_id' => '123',
            'title' => 'Task'
        ]);
    }

    /**
     * Test: execute() throws RuntimeException if getTaskByID() returns null.
     *
     * @return void
     */
    public function testGetTaskByIdReturnsNullThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok(null, 0));

        $this->expectException(RuntimeException::class);

        $this->service->execute([
            'id' => '1',
            'user_id' => '123',
            'title' => 'Task'
        ]);
    }

    /**
     * Test: execute() throws RuntimeException if updateTask() fails.
     *
     * @return void
     */
    public function testUpdateTaskFailsThrowsRuntimeException(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'description' => '', 'is_done' => 0];

        // Mock getTaskByID to succeed
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        // Mock updateTask to fail
        $this->taskQueries->method('updateTask')
            ->willReturn(QueryResult::fail(['Update error']));

        $this->expectException(RuntimeException::class);

        $this->service->execute([
            'id' => '1',
            'user_id' => '123',
            'title' => 'Task'
        ]);
    }

    /**
     * Test: execute() throws RuntimeException if updateTask() affected 0 rows.
     *
     * @return void
     */
    public function testUpdateTaskNotChangedThrowsRuntimeException(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'description' => '', 'is_done' => 0];

        // Mock getTaskByID to succeed
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        // Mock updateTask to succeed but affected=0
        $this->taskQueries->method('updateTask')
            ->willReturn(QueryResult::ok($task, 0));

        $this->expectException(RuntimeException::class);

        $this->service->execute([
            'id' => '1',
            'user_id' => '123',
            'title' => 'Task'
        ]);
    }

    /**
     * Test: execute() returns updated task and total pages on success.
     *
     * @return void
     */
    public function testUpdateTaskSuccessReturnsExpectedArray(): void
    {
        $task = ['id' => 1, 'title' => 'Updated Task', 'description' => 'Desc', 'is_done' => 1];

        // Mock getTaskByID to return existing task
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        // Mock updateTask to succeed
        $this->taskQueries->method('updateTask')
            ->willReturn(QueryResult::ok($task, 1));

        // Mock getTotalTasks to calculate total pages
        $this->taskQueries->method('getTotalTasks')
            ->willReturn(QueryResult::ok(25));

        $result = $this->service->execute([
            'id' => '1',
            'user_id' => '123',
            'title' => 'Updated Task',
            'description' => 'Desc',
            'is_done' => 1
        ]);

        // Assert returned task and pagination
        $this->assertSame($task, $result['task']);
        $this->assertSame(3, $result['totalPages']); // ceil(25/10)
    }
}
