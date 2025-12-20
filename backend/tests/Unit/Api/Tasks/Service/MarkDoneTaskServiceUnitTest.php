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
     * Test that missing task ID throws exception.
     * 
     * @return void
     */
    public function testMissingIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([], [], [], 'POST', '/', ['id' => '123']); // Missing 'id'
        $this->service->execute($req);
    }

    /**
     * Test that invalid 'is_done' value triggers an Error.
     * 
     * @return void
     */
    public function testInvalidStatusValueThrowsException(): void
    {
        // Mock task existence as logic now checks task first
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok(['id' => 1, 'is_done' => 0], 1));

        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'is_done' => 2 // Invalid status, should be 0 or 1
        ], [], [], 'POST', '/', ['id' => '123']);
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
            'is_done' => 1
        ], [], [], 'POST', '/', ['id' => '123']);
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
            'is_done' => 1
        ], [], [], 'POST', '/', ['id' => '123']);
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
            'is_done' => 1
        ], [], [], 'POST', '/', ['id' => '123']);
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
            'is_done' => 1
        ], [], [], 'POST', '/', ['id' => '123']);
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
            'is_done' => 1
        ], [], [], 'POST', '/', ['id' => '123']);
        $this->service->execute($req);
    }

    /**
     * Test successful markDone execution.
     * 
     * @return void
     */
    public function testMarkDoneSuccessReturnsExpectedArray(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'is_done' => 1, 'user_id' => 123, 'created_at' => '2023-01-01'];

        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('markDone')
            ->willReturn(QueryResult::ok($task, 1));

        $req = $this->makeRequest([
            'id' => '1',
            'is_done' => 1
        ], [], [], 'POST', '/', ['id' => '123']);

        $result = $this->service->execute($req);

        $expectedTask = $task;
        unset($expectedTask['user_id']);
        unset($expectedTask['created_at']);

        $this->assertEquals($expectedTask, $result['task']);
        $this->assertArrayNotHasKey('totalPages', $result);
    }

    /**
     * Test that 'is_done' accepts string "0" and "1".
     * 
     * @return void
     */
    public function testIsDoneAcceptsStringZeroOrOne(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'is_done' => 1, 'user_id' => 123, 'created_at' => '2023-01-01'];

        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('markDone')
            ->willReturn(QueryResult::ok($task, 1));

        $expectedTask = $task;
        unset($expectedTask['user_id']);
        unset($expectedTask['created_at']);

        // --- case "0"
        $req = $this->makeRequest([
            'id' => '1',
            'is_done' => "0"
        ], [], [], 'POST', '/', ['id' => '123']);
        $result = $this->service->execute($req);

        $this->assertSame($expectedTask, $result['task']);

        // --- case "1"
        $req = $this->makeRequest([
            'id' => '1',
            'is_done' => "1"
        ], [], [], 'POST', '/', ['id' => '123']);
        $result = $this->service->execute($req);

        $this->assertSame($expectedTask, $result['task']);
    }

    /**
     * Test that invalid string for 'is_done' throws Error.
     * 
     * @return void
     */
    public function testIsDoneInvalidStringThrowsException(): void
    {
        // Mock task existence
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok(['id' => 1, 'is_done' => 0], 1));

        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'is_done' => "abc" // Invalid string
        ], [], [], 'POST', '/', ['id' => '123']);
        $this->service->execute($req);
    }

    /**
     * Test that missing 'is_done' parameter triggers exception.
     * 
     * @return void
     */
    /**
     * Test that missing 'is_done' parameter toggles the current status.
     * 
     * @return void
     */
    public function testIsDoneMissingTogglesStatus(): void
    {
        // Case 1: Current status is 0 (not done) -> should likely become 1 (done)
        $taskNotDone = ['id' => 1, 'title' => 'Task', 'is_done' => 0, 'user_id' => 123, 'created_at' => '2023-01-01'];

        // Mock getTaskByID returning not done task
        $this->taskQueries->expects($this->atMost(2)) // We will run 2 calls potentially
            ->method('getTaskByID')
            ->willReturn(QueryResult::ok($taskNotDone, 1));

        // Expect markDone to be called with true (toggling 0 -> 1)
        $this->taskQueries->expects($this->once())
            ->method('markDone')
            ->with(1, true, 123)
            ->willReturn(QueryResult::ok(['id' => 1, 'is_done' => 1], 1));

        $req = $this->makeRequest([
            'id' => '1',
        ], [], [], 'POST', '/', ['id' => '123']); // Missing 'is_done'

        $this->service->execute($req);
    }
}
