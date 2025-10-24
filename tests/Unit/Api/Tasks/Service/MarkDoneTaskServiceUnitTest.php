<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\MarkDoneTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;
use Error;

/**
 * Class MarkDoneTaskServiceTest
 *
 * Unit tests for MarkDoneTaskService.
 *
 * Covers scenarios such as:
 * - Missing or invalid input parameters
 * - Query failures or unexpected DB results
 * - Successful marking of task as done
 *
 * Uses API test helper trait for convenient Request creation.
 *
 * @package Tests\Unit\Api\Tasks\Service
 */
class MarkDoneTaskServiceUnitTest extends TestCase
{
    /** 
     * @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject 
     * Mocked DB layer for controlling query results
     */
    private $taskQueries;

    private MarkDoneTaskService $service;

    use ApiTestHelperTrait;

    /**
     * Set up mocks and service instance before each test.
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new MarkDoneTaskService($this->taskQueries);
    }

    /**
     * Test that missing task ID or user ID triggers exception.
     * 
     * @return void
     */
    public function testMissingIdOrUserIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest(['user_id' => '123']); // Missing 'id'
        $this->service->execute($req);
    }

    /**
     * Test that invalid 'is_done' value triggers an Error.
     * 
     * @return void
     */
    public function testInvalidStatusValueThrowsException(): void
    {
        $this->expectException(Error::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => 2 // Invalid status, should be 0 or 1
        ]);
        $this->service->execute($req);
    }

    /**
     * Test that non-numeric task ID triggers exception.
     * 
     * @return void
     */
    public function testNonNumericTaskIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => 'abc', // Non-numeric
            'user_id' => '123',
            'is_done' => 1
        ]);
        $this->service->execute($req);
    }

    /**
     * Test behavior when getTaskByID query fails.
     * 
     * @return void
     */
    public function testGetTaskByIdFailsThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::fail(['DB error'])); // Simulate DB failure

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => 1
        ]);
        $this->service->execute($req);
    }

    /**
     * Test behavior when getTaskByID returns null (task not found).
     * 
     * @return void
     */
    public function testGetTaskByIdReturnsNullThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok(null, 0)); // Task not found

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => 1
        ]);
        $this->service->execute($req);
    }

    /**
     * Test that markDone failure triggers RuntimeException.
     * 
     * @return void
     */
    public function testMarkDoneFailsThrowsRuntimeException(): void
    {
        // Mock getTaskByID to return existing task
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok(['id' => 1, 'title' => 'Task'], 1));

        // Mock markDone to fail
        $this->taskQueries->method('markDone')
            ->willReturn(QueryResult::fail(['Update error']));

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => 1
        ]);
        $this->service->execute($req);
    }

    /**
     * Test that markDone returning 0 affected rows triggers exception.
     * 
     * @return void
     */
    public function testMarkDoneNotChangedThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok(['id' => 1, 'title' => 'Task'], 1));

        $this->taskQueries->method('markDone')
            ->willReturn(QueryResult::ok(['id' => 1, 'title' => 'Task'], 0)); // No row affected

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => 1
        ]);
        $this->service->execute($req);
    }

    /**
     * Test successful markDone execution.
     * 
     * @return void
     */
    public function testMarkDoneSuccessReturnsExpectedArray(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'is_done' => 1];

        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('markDone')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('getTotalTasks')
            ->willReturn(QueryResult::ok(15)); // For pagination calculation

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => 1
        ]);

        $result = $this->service->execute($req);

        $this->assertSame($task, $result['task']);
        $this->assertSame(2, $result['totalPages']); // ceil(15 / 10)
    }

    /**
     * Test that 'is_done' accepts string "0" and "1".
     * 
     * @return void
     */
    public function testIsDoneAcceptsStringZeroOrOne(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'is_done' => 1];

        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('markDone')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('getTotalTasks')
            ->willReturn(QueryResult::ok(5));

        // --- case "0"
        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => "0"
        ]);
        $result = $this->service->execute($req);

        $this->assertSame($task, $result['task']);

        // --- case "1"
        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => "1"
        ]);
        $result = $this->service->execute($req);

        $this->assertSame($task, $result['task']);
    }

    /**
     * Test that invalid string for 'is_done' throws Error.
     * 
     * @return void
     */
    public function testIsDoneInvalidStringThrowsException(): void
    {
        $this->expectException(Error::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => "abc" // Invalid string
        ]);
        $this->service->execute($req);
    }

    /**
     * Test that missing 'is_done' parameter triggers exception.
     * 
     * @return void
     */
    public function testIsDoneMissingThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123' // Missing 'is_done'
        ]);
        $this->service->execute($req);
    }
}
