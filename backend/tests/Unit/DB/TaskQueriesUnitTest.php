<?php

declare(strict_types=1);

namespace Tests\Unit\DB;

use PHPUnit\Framework\TestCase;
use App\DB\TaskQueries;
use PDOStatement;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class TaskQueriesTest
 *
 * Unit tests for the TaskQueries class.
 *
 * This test suite verifies CRUD and task management operations:
 * - addTask(), getAllTasks(), getTaskByID(), getTasksByPage()
 * - getTotalTasks(), markDone(), updateTask(), deleteTask()
 *
 * Uses PDOStatement and PDO mocks to avoid real database connections.
 *
 * @package Tests\Unit\DB
 */
class TaskQueriesUnitTest extends TestCase
{
    /** @var PDO&MockObject */
    private PDO $pdo;

    /** @var PDOStatement&MockObject */
    private PDOStatement $stmt;

    private TaskQueries $taskQueries;

    /**
     * Setup mocks for PDO and PDOStatement before each test.
     *
     * Mocks execute, fetch, fetchAll, rowCount, fetchColumn, and lastInsertId.
     * Injects mocked PDO into TaskQueries instance.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Create a mock PDOStatement to replace a real statement
        $this->stmt = $this->createMock(PDOStatement::class);

        // Create a mock PDO to replace a real database connection
        $this->pdo = $this->createMock(PDO::class);

        // Make PDO mock return the PDOStatement mock when prepare() is called
        $this->pdo->method('prepare')->willReturn($this->stmt);

        // Make PDO mock return '1' when lastInsertId() is called
        $this->pdo->method('lastInsertId')->willReturn('1');

        // Instantiate TaskQueries with the mocked PDO connection
        $this->taskQueries = new TaskQueries($this->pdo);
    }

    /** ----------------- addTask ----------------- */
    /**
     * Test: addTask should return success with proper data when execute succeeds.
     * 
     * @return void
     */
    public function testAddTaskSuccess(): void
    {
        // Mock successful execute and return a sample task
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn([
            'id' => 1,
            'title' => 'Test',
            'description' => 'Desc'
        ]);

        // Call addTask
        $result = $this->taskQueries->addTask('Test', 'Desc', 'testuserid');

        // Assert success and correct returned data
        $this->assertTrue($result->success);
        $this->assertEquals([
            'id' => 1,
            'title' => 'Test',
            'description' => 'Desc'
        ], $result->data);
    }

    /**
     * Test: addTask should return failure with error info when execute fails.
     * 
     * @return void
     */
    public function testAddTaskFail(): void
    {
        // Mock execute failure and return error info
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn([
            '123',
            '456',
            'DB error'
        ]);

        // Call addTask
        $result = $this->taskQueries->addTask('Fail', 'Desc', 'testuserid');

        // Assert failure and correct error info
        $this->assertFalse($result->success);
        $this->assertEquals(['123', '456', 'DB error'], $result->error);
    }

    /**
     * Test: addTask returns ok but fetch() after insert gives false (no task found).
     * 
     * @return void
     */
    public function testAddTaskInsertedButNotFetched(): void
    {
        // Mock successful execute
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn(false); // simulate no row found

        // Call addTask
        $result = $this->taskQueries->addTask('Title', 'Desc', 'testuserid');

        $this->assertTrue($result->success);
        $this->assertNull($result->data);
        $this->assertEquals(0, $result->affected);
    }

    /** ----------------- getAllTasks ----------------- */
    /**
     * Test: getAllTasks should return all tasks successfully.
     * 
     * @return void
     */
    public function testGetAllTasksSuccess(): void
    {
        // Prepare mock data and behavior
        $tasks = [['id' => 1, 'title' => 'T1'], ['id' => 2, 'title' => 'T2']];
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchAll')->willReturn($tasks);
        $this->stmt->method('rowCount')->willReturn(2);

        // Call getAllTasks
        $result = $this->taskQueries->getAllTasks();

        // Assert success and correct task list
        $this->assertTrue($result->success);
        $this->assertEquals($tasks, $result->data);
        $this->assertEquals(2, $result->affected);
    }

    /**
     * Test: getAllTasks should return failure if execute fails.
     * 
     * @return void
     */
    public function testGetAllTasksFail(): void
    {
        // Mock execute failure
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn(['code' => '999']);

        // Call getAllTasks
        $result = $this->taskQueries->getAllTasks();

        // Assert failure
        $this->assertFalse($result->success);
    }

    /**
     * Test: failFromStmt with errorInfo missing keys (edge case).
     * 
     * @return void
     */
    public function testFailFromStmtIncompleteErrorInfo(): void
    {
        // Mock execute failure
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn(['oops']);

        // Call getAllTasks
        $result = $this->taskQueries->getAllTasks();

        // Assert failure
        $this->assertFalse($result->success);
        $this->assertEquals(['oops'], $result->error);
    }

    /** ----------------- getTaskByID ----------------- */
    /**
     * Test: getTaskByID should return task when found.
     * 
     * @return void
     */
    public function testGetTaskByIDFound(): void
    {
        // Mock execute success and return a single task
        $task = ['id' => 1, 'title' => 'T1'];
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn($task);

        // Call getTaskByID
        $result = $this->taskQueries->getTaskByID(1, 'testuserid');

        // Assert success and correct task
        $this->assertTrue($result->success);
        $this->assertEquals($task, $result->data);
        $this->assertEquals(1, $result->affected);
    }

    /**
     * Test: getTaskByID should return null data if task not found.
     * 
     * @return void
     */
    public function testGetTaskByIDNotFound(): void
    {
        // Mock execute success but no task found
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn(false);

        // Call getTaskByID with non-existing ID
        $result = $this->taskQueries->getTaskByID(999, 'testuserid');

        // Assert success with null data
        $this->assertTrue($result->success);
        $this->assertNull($result->data);
        $this->assertEquals(0, $result->affected);
    }

    /**
     * Test: getTaskByID should return failure if execute fails.
     * 
     * @return void
     */
    public function testGetTaskByIDFail(): void
    {
        // Mock execute failure
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn(['code' => 'err']);

        // Call getTaskByID
        $result = $this->taskQueries->getTaskByID(1, 'testuserid');

        // Assert failure
        $this->assertFalse($result->success);
    }

    /** ----------------- getTasksByPage ----------------- */
    /**
     * Test: getTasksByPage should return tasks for a page successfully.
     * 
     * @return void
     */
    public function testGetTasksByPageSuccess(): void
    {
        // Mock tasks for pagination
        $tasks = [['id' => 1, 'title' => 'T1']];
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchAll')->willReturn($tasks);
        $this->stmt->method('rowCount')->willReturn(1);

        // Call getTasksByPage
        $result = $this->taskQueries->getTasksByPage(1, 10);

        // Assert success and returned tasks
        $this->assertTrue($result->success);
        $this->assertEquals($tasks, $result->data);
        $this->assertEquals(1, $result->affected);
    }

    /**
     * Test: getTasksByPage should return failure if execute fails.
     * 
     * @return void
     */
    public function testGetTasksByPageFail(): void
    {
        // Mock execute failure
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn(['code' => 'err']);

        // Call getTasksByPage
        $result = $this->taskQueries->getTasksByPage(1, 10);

        // Assert failure
        $this->assertFalse($result->success);
    }

    /**
     * Test: getTasksByPage with page=0 should calculate negative offset (edge case).
     * 
     * @return void
     */
    public function testGetTasksByPageZeroPage(): void
    {
        $tasks = [];
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchAll')->willReturn($tasks);

        // Call getTasksByPage
        $result = $this->taskQueries->getTasksByPage(0, 10);

        $this->assertTrue($result->success);
        $this->assertSame([], $result->data);
    }

    /**
     * Test: getTasksByPage with perPage=0 should return empty results.
     * 
     * @return void
     */
    public function testGetTasksByPagePerPageZero(): void
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchAll')->willReturn([]);

        // Call getTasksByPage
        $result = $this->taskQueries->getTasksByPage(1, 0);

        $this->assertTrue($result->success);
        $this->assertSame([], $result->data);
        $this->assertEquals(0, $result->affected);
    }

    /**
     * Test: getTasksByPage with userId filter.
     * 
     * @return void
     */
    public function testGetTasksByPageWithUserId(): void
    {
        $tasks = [['id' => 10, 'user_id' => 'u1']];
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchAll')->willReturn($tasks);

        // Call getTasksByPage
        $result = $this->taskQueries->getTasksByPage(1, 5, 'u1');

        $this->assertTrue($result->success);
        $this->assertEquals($tasks, $result->data);
    }



    /** ----------------- markDone ----------------- */
    /**
     * Test: markDone should update task completion status successfully.
     * 
     * @return void
     */
    public function testMarkDoneSuccess(): void
    {
        // Mock execute success and returned updated task
        $task = ['id' => 1, 'title' => 'T1', 'is_done' => 1];
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn($task);

        // Call markDone
        $result = $this->taskQueries->markDone(1, true, 'testuserid');

        // Assert success and correct updated task
        $this->assertTrue($result->success);
        $this->assertEquals($task, $result->data);
    }

    /**
     * Test: markDone should return failure if execute fails.
     * 
     * @return void
     */
    public function testMarkDoneFail(): void
    {
        // Mock execute failure
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn(['code' => 'err']);

        // Call markDone
        $result = $this->taskQueries->markDone(1, true, 'testuserid');

        // Assert failure
        $this->assertFalse($result->success);
    }

    /**
     * Test: markDone with isDone=false should map to 0.
     * 
     * @return void
     */
    public function testMarkDoneFalse(): void
    {
        $task = ['id' => 2, 'title' => 'T2', 'is_done' => 0];
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn($task);

        // Call getTotalTasks
        $result = $this->taskQueries->markDone(2, false, 'testuserid');

        $this->assertIsArray($result->data);
        $this->assertEquals(0, $result->data['is_done']);
    }

    /** ----------------- updateTask ----------------- */
    /**
     * Test: updateTask should modify task details successfully.
     * 
     * @return void
     */
    public function testUpdateTaskSuccess(): void
    {
        // Mock execute success and returned updated task
        $task = ['id' => 1, 'title' => 'New', 'is_done' => 1];
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn($task);

        // Call updateTask
        $result = $this->taskQueries->updateTask(1, 'New', 'Desc', true, 'testuserid');

        // Assert success and correct updated task
        $this->assertTrue($result->success);
        $this->assertEquals($task, $result->data);
    }

    /**
     * Test: updateTask should return failure if execute fails.
     * 
     * @return void
     */
    public function testUpdateTaskFail(): void
    {
        // Mock execute failure
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn(['code' => 'err']);

        // Call updateTask
        $result = $this->taskQueries->updateTask(1, 'New', 'Desc', true, 'testuserid');

        // Assert failure
        $this->assertFalse($result->success);
    }

    /**
     * Test: updateTask returns ok but affected=0 (no row updated).
     * 
     * @return void
     */
    public function testUpdateTaskNoRowAffected(): void
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn(false);

        // Call updateTask
        $result = $this->taskQueries->updateTask(99, 'Nope', 'N/A', false, 'testuserid');

        // Assert success and correct updated task
        $this->assertTrue($result->success);
        $this->assertNull($result->data);
        $this->assertEquals(0, $result->affected);
    }

    /** ----------------- deleteTask ----------------- */
    /**
     * Test: deleteTask should remove task successfully.
     * 
     * @return void
     */
    public function testDeleteTaskSuccess(): void
    {
        // Mock execute success and affected row count
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('rowCount')->willReturn(1);

        // Call deleteTask
        $result = $this->taskQueries->deleteTask(1, 'testuserid');

        // Assert success and correct affected rows
        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->affected);
    }

    /**
     * Test: deleteTask should return failure if execute fails.
     * 
     * @return void
     */
    public function testDeleteTaskFail(): void
    {
        // Mock execute failure
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn(['code' => 'err']);

        // Call deleteTask
        $result = $this->taskQueries->deleteTask(1, 'testuserid');

        // Assert failure
        $this->assertFalse($result->success);
    }

    /**
     * Test: deleteTask executes successfully but no rows deleted.
     * 
     * @return void
     */
    public function testDeleteTaskNoRows(): void
    {
        // Mock execute success and affected row count
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('rowCount')->willReturn(0);

        // Call deleteTask
        $result = $this->taskQueries->deleteTask(123, 'testuserid');

        // Assert success and correct affected rows
        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->affected);
    }
}
