<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\UpdateTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class UpdateTaskServiceTest
 *
 * Unit tests for the UpdateTaskService class.
 *
 * Covers scenarios including:
 * - Missing required fields (id, user_id, title)
 * - Invalid status values
 * - Task retrieval failures
 * - Update failures or no changes
 * - Successful task updates with correct totalPages calculation
 *
 * Uses the TestHelperTrait for request generation.
 *
 * @package Tests\Unit\Api\Tasks\Service
 */
class UpdateTaskServiceUnitTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject Mocked TaskQueries dependency */
    private $taskQueries;

    /** @var UpdateTaskService Service under test */
    private UpdateTaskService $service;

    use ApiTestHelperTrait;

    /**
     * Set up the test environment.
     *
     * Creates a mocked TaskQueries instance and initializes
     * the UpdateTaskService with it.
     */
    protected function setUp(): void
    {
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new UpdateTaskService($this->taskQueries);
    }

    /**
     * Test that missing id throws InvalidArgumentException.
     */
    public function testMissingIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([], [], [], 'POST', '/', ['id' => '123']); // id missing
        $this->service->execute($req);
    }

    /**
     * Test that missing title throws InvalidArgumentException.
     */
    public function testMissingTitleThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest(['id' => '1'], [], [], 'POST', '/', ['id' => '123']); // title missing
        $this->service->execute($req);
    }

    /**
     * Test that invalid status value is normalized to 0.
     *
     * Also checks that totalPages calculation works correctly.
     */
    public function testInvalidStatusValueDefaultsToZero(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'description' => '', 'is_done' => 0];

        // Stub getTaskByID to return existing task
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        // Stub updateTask to simulate successful update
        $this->taskQueries->method('updateTask')
            ->willReturn(QueryResult::ok($task, 1));

        // Stub getTotalTasks for pagination
        $this->taskQueries->method('getTotalTasks')
            ->willReturn(QueryResult::ok(12));

        $req = $this->makeRequest([
            'id' => '1',
            'title' => 'Task',
            'description' => '',
            'is_done' => 2 // invalid -> defaults to 0
        ], [], [], 'POST', '/', ['id' => '123']);

        $result = $this->service->execute($req);

        // Verify task returned and totalPages calculated correctly
        $this->assertSame($task, $result['task']);
        $this->assertSame(2, $result['totalPages']);
    }

    /**
     * Test that non-numeric task id throws InvalidArgumentException.
     */
    public function testNonNumericTaskIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => 'abc', // invalid ID
            'title' => 'Task'
        ], [], [], 'POST', '/', ['id' => '123']);

        $this->service->execute($req);
    }

    /**
     * Test that failing getTaskByID throws InvalidArgumentException.
     */
    public function testGetTaskByIdFailsThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::fail(['DB error']));

        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'title' => 'Task'
        ], [], [], 'POST', '/', ['id' => '123']);

        $this->service->execute($req);
    }

    /**
     * Test that getTaskByID returning null throws InvalidArgumentException.
     */
    public function testGetTaskByIdReturnsNullThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok(null, 0));

        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'title' => 'Task'
        ], [], [], 'POST', '/', ['id' => '123']);

        $this->service->execute($req);
    }

    /**
     * Test that updateTask failure throws InvalidArgumentException.
     */
    public function testUpdateTaskFailsThrowsRuntimeException(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'description' => '', 'is_done' => 0];

        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('updateTask')
            ->willReturn(QueryResult::fail(['Update error']));

        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'title' => 'Task'
        ], [], [], 'POST', '/', ['id' => '123']);

        $this->service->execute($req);
    }

    /**
     * Test that updateTask returning 0 affected rows throws InvalidArgumentException.
     */
    public function testUpdateTaskNotChangedThrowsRuntimeException(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'description' => '', 'is_done' => 0];

        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('updateTask')
            ->willReturn(QueryResult::ok($task, 0)); // no rows affected

        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'title' => 'Task'
        ], [], [], 'POST', '/', ['id' => '123']);

        $this->service->execute($req);
    }

    /**
     * Test successful task update returns expected array with totalPages.
     */
    public function testUpdateTaskSuccessReturnsExpectedArray(): void
    {
        $task = ['id' => 1, 'title' => 'Updated Task', 'description' => 'Desc', 'is_done' => 1];

        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('updateTask')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('getTotalTasks')
            ->willReturn(QueryResult::ok(25));

        $req = $this->makeRequest([
            'id' => '1',
            'title' => 'Updated Task',
            'description' => 'Desc',
            'is_done' => 1
        ], [], [], 'POST', '/', ['id' => '123']);

        $result = $this->service->execute($req);

        // Verify returned task data and totalPages calculation
        $this->assertSame($task, $result['task']);
        $this->assertSame(3, $result['totalPages']);
    }
}
