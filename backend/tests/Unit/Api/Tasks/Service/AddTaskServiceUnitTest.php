<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\AddTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use Error;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class AddTaskServiceTest
 *
 * Unit tests for AddTaskService class.
 *
 * Covers validation and database interaction logic:
 * - Missing required fields (title, user_id)
 * - Query failures and edge cases (no rows changed, no data returned)
 * - Successful task creation and pagination result
 *
 * Uses PHPUnit mocks to simulate TaskQueries behavior and
 * TestHelperTrait for request construction.
 *
 * @package Tests\Unit\Api\Tasks\Service
 */
class AddTaskServiceUnitTest extends TestCase
{
    /** 
     * Mocked TaskQueries dependency used for database simulation.
     *
     * @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject 
     */
    private $taskQueries;

    /** @var AddTaskService Service under test */
    private AddTaskService $service;

    /**
     * Setup before each test.
     *
     * Creates a mock TaskQueries instance and initializes
     * the AddTaskService with it.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new AddTaskService($this->taskQueries);
    }

    // Use helper trait for generating mock Request instances
    use ApiTestHelperTrait;

    /**
     * Ensure exception is thrown if "title" is missing from request body.
     *
     * @return void
     */
    public function testThrowsIfTitleMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Missing 'title'
        $req = $this->makeRequest(['description' => 'desc', 'user_id' => '1']);
        $this->service->execute($req);
    }

    /**
     * Ensure exception is thrown if "user_id" is missing.
     *
     * @return void
     */
    public function testThrowsIfUserIdMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Missing 'user_id'
        $req = $this->makeRequest(['title' => 'My Task', 'description' => 'desc']);
        $this->service->execute($req);
    }

    /**
     * Ensure RuntimeException is thrown when addTask() fails at DB layer.
     *
     * Simulates database failure via QueryResult::fail().
     *
     * @return void
     */
    public function testThrowsIfAddTaskFails(): void
    {
        $this->taskQueries
            ->expects($this->once())
            ->method('addTask')
            ->willReturn(QueryResult::fail(['db error'])); // Simulate DB error

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest([
            'title' => 'My Task',
            'description' => 'desc',
            'user_id' => '1',
        ]);
        $this->service->execute($req);
    }

    /**
     * Ensure RuntimeException is thrown when addTask() succeeds
     * but no database rows are changed (0 affected).
     *
     * @return void
     */
    public function testThrowsIfNoRowsChanged(): void
    {
        $result = QueryResult::ok(['id' => 1], 0); // No rows affected

        $this->taskQueries
            ->expects($this->once())
            ->method('addTask')
            ->willReturn($result);

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest([
            'title' => 'My Task',
            'description' => 'desc',
            'user_id' => '1',
        ]);
        $this->service->execute($req);
    }

    /**
     * Ensure Error is thrown when addTask() returns OK result
     * but no data is included (empty array).
     *
     * @return void
     */
    public function testThrowsIfNoDataReturned(): void
    {
        $result = QueryResult::ok([], 1); // No data returned

        $this->taskQueries
            ->expects($this->once())
            ->method('addTask')
            ->willReturn($result);

        $this->expectException(Error::class);

        $req = $this->makeRequest([
            'title' => 'My Task',
            'description' => 'desc',
            'user_id' => '1',
        ]);
        $this->service->execute($req);
    }

    /**
     * Ensure successful execution returns correct task data
     * and total pages count.
     *
     * Simulates both addTask() and getTotalTasks() returning
     * valid data to compute pagination correctly.
     *
     * @return void
     */
    public function testReturnsTaskAndTotalPagesOnSuccess(): void
    {
        $taskData = ['id' => 1, 'title' => 'My Task', 'user_id' => 1];
        $result = QueryResult::ok($taskData, 1); // DB success

        // Simulate task insertion success
        $this->taskQueries
            ->expects($this->once())
            ->method('addTask')
            ->willReturn($result);

        // Simulate total tasks query returning 25 tasks
        $this->taskQueries
            ->expects($this->once())
            ->method('getTotalTasks')
            ->willReturn(QueryResult::ok(25));

        // Build valid request
        $req = $this->makeRequest([
            'title' => 'My Task',
            'description' => 'desc',
            'user_id' => '1',
        ]);

        // Execute service and validate result
        $output = $this->service->execute($req);

        // Assert both task data and computed pagination
        $this->assertEquals($taskData, $output['task']);
        $this->assertEquals(3, $output['totalPages']);
    }
}
