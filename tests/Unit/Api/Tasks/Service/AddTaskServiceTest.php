<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use App\Api\Tasks\Service\AddTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class AddTaskServiceTest
 *
 * Unit tests for AddTaskService.
 *
 * This test suite verifies:
 * - Validation for required fields (title, user_id)
 * - Handling of failed database operations
 * - Correct return structure with task data and total pages
 *
 * @package Tests\Unit\Api\Tasks\Service
 */
class AddTaskServiceTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    private AddTaskService $service;

    /**
     * Setup mocks and service instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Mock TaskQueries to isolate service logic
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new AddTaskService($this->taskQueries);
    }

    /**
     * Test: execute() throws InvalidArgumentException if title is missing.
     *
     * @return void
     */
    public function testThrowsIfTitleMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Call service with missing 'title'
        $this->service->execute([
            'description' => 'desc',
            'user_id' => '1',
        ]);
    }

    /**
     * Test: execute() throws InvalidArgumentException if user_id is missing.
     *
     * @return void
     */
    public function testThrowsIfUserIdMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Call service with missing 'user_id'
        $this->service->execute([
            'title' => 'My Task',
            'description' => 'desc',
        ]);
    }

    /**
     * Test: execute() throws RuntimeException if addTask() fails.
     *
     * @return void
     */
    public function testThrowsIfAddTaskFails(): void
    {
        // Mock addTask to return failure
        $this->taskQueries
            ->expects($this->once())
            ->method('addTask')
            ->willReturn(QueryResult::fail(['db error']));

        $this->expectException(RuntimeException::class);

        $this->service->execute([
            'title' => 'My Task',
            'description' => 'desc',
            'user_id' => '1',
        ]);
    }

    /**
     * Test: execute() throws RuntimeException if no rows were affected.
     *
     * @return void
     */
    public function testThrowsIfNoRowsChanged(): void
    {
        $result = QueryResult::ok(['id' => 1], 0); // affected = 0

        $this->taskQueries
            ->expects($this->once())
            ->method('addTask')
            ->willReturn($result);

        $this->expectException(RuntimeException::class);

        $this->service->execute([
            'title' => 'My Task',
            'description' => 'desc',
            'user_id' => '1',
        ]);
    }

    /**
     * Test: execute() throws RuntimeException if no data is returned.
     *
     * @return void
     */
    public function testThrowsIfNoDataReturned(): void
    {
        $result = QueryResult::ok([], 1); // data empty

        $this->taskQueries
            ->expects($this->once())
            ->method('addTask')
            ->willReturn($result);

        $this->expectException(RuntimeException::class);

        $this->service->execute([
            'title' => 'My Task',
            'description' => 'desc',
            'user_id' => '1',
        ]);
    }

    /**
     * Test: execute() returns task data and total pages on success.
     *
     * @return void
     */
    public function testReturnsTaskAndTotalPagesOnSuccess(): void
    {
        $taskData = ['id' => 1, 'title' => 'My Task', 'user_id' => 1];
        $result = QueryResult::ok($taskData, 1);

        // Mock addTask to return successful result
        $this->taskQueries
            ->expects($this->once())
            ->method('addTask')
            ->willReturn($result);

        // Mock getTotalTasks to calculate total pages
        $this->taskQueries
            ->expects($this->once())
            ->method('getTotalTasks')
            ->willReturn(QueryResult::ok(25));

        // Call service
        $output = $this->service->execute([
            'title' => 'My Task',
            'description' => 'desc',
            'user_id' => '1',
        ]);

        // Assert task data and totalPages
        $this->assertEquals($taskData, $output['task']);
        $this->assertEquals(3, $output['totalPages']); // ceil(25 / 10) = 3
    }
}
