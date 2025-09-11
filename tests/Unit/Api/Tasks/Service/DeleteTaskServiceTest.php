<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use App\Api\Tasks\Service\DeleteTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class DeleteTaskServiceTest
 *
 * Unit tests for DeleteTaskService.
 *
 * This test suite verifies:
 * - Validation for required fields (id, user_id)
 * - Handling of non-numeric task IDs
 * - Handling of failed database operations
 * - Correct return structure with deleted task ID and total pages
 *
 * @package Tests\Unit\Api\Tasks\Service
 */
class DeleteTaskServiceTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    private DeleteTaskService $service;

    /**
     * Setup mocks and service instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Mock TaskQueries to isolate service logic
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new DeleteTaskService($this->taskQueries);
    }

    /**
     * Test: execute() throws InvalidArgumentException if task id is missing.
     *
     * @return void
     */
    public function testMissingTaskIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Call service without 'id'
        $this->service->execute(['user_id' => '123']);
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
        $this->service->execute(['id' => '10']);
    }

    /**
     * Test: execute() throws InvalidArgumentException if task id is non-numeric.
     *
     * @return void
     */
    public function testNonNumericTaskIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Call service with non-numeric id
        $this->service->execute(['id' => 'abc', 'user_id' => '123']);
    }

    /**
     * Test: execute() throws RuntimeException if deleteTask() fails.
     *
     * @return void
     */
    public function testDeleteFailsReturnsRuntimeException(): void
    {
        // Mock deleteTask to return failure
        $this->taskQueries->method('deleteTask')
            ->willReturn(QueryResult::fail(['SQL error']));

        $this->expectException(RuntimeException::class);

        $this->service->execute(['id' => '1', 'user_id' => '123']);
    }

    /**
     * Test: execute() throws RuntimeException if deleteTask() affected 0 rows.
     *
     * @return void
     */
    public function testDeleteNotChangedThrowsRuntimeException(): void
    {
        // Mock deleteTask to succeed but affect 0 rows
        $this->taskQueries->method('deleteTask')
            ->willReturn(QueryResult::ok(null, 0));

        $this->expectException(RuntimeException::class);

        $this->service->execute(['id' => '1', 'user_id' => '123']);
    }

    /**
     * Test: execute() returns expected array on successful deletion.
     *
     * @return void
     */
    public function testDeleteTaskSuccessReturnsExpectedArray(): void
    {
        // Mock deleteTask to succeed and affect 1 row
        $this->taskQueries->method('deleteTask')
            ->willReturn(QueryResult::ok(null, 1));

        // Mock getTotalTasks to calculate total pages
        $this->taskQueries->method('getTotalTasks')
            ->willReturn(QueryResult::ok(25));

        $result = $this->service->execute(['id' => '1', 'user_id' => '123']);

        // Assert structure and values
        $this->assertIsArray($result);
        $this->assertSame(1, $result['id']);
        $this->assertSame(3, $result['totalPages']); // ceil(25 / 10)
    }
}
