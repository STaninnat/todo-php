<?php

namespace Tests\Unit\DB;

use PHPUnit\Framework\TestCase;
use App\DB\TaskQueries;
use PDOStatement;
use PDO;

/**
 * Unit tests for the TaskQueries class.
 *
 * This test suite verifies CRUD and task management operations:
 * - addTask(), getAllTasks(), getTaskByID(), getTasksByPage()
 * - getTotalTasks(), markDone(), updateTask(), deleteTask()
 *
 * Uses PDOStatement and PDO mocks to avoid real database connections.
 */
class TaskQueriesTest extends TestCase
{
    private $pdo;
    private $stmt;
    private TaskQueries $taskQueries;

    /**
     * Setup mocks for PDO and PDOStatement before each test.
     *
     * - Mock execute, fetch, fetchAll, rowCount, fetchColumn, lastInsertId
     * - Inject mocked PDO into TaskQueries instance
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
     */
    public function testAddTaskSuccess()
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
     */
    public function testAddTaskFail()
    {
        // Mock execute failure and return error info
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn([
            'code' => '123',
            'message' => 'DB error'
        ]);

        // Call addTask
        $result = $this->taskQueries->addTask('Fail', 'Desc', 'testuserid');

        // Assert failure and correct error info
        $this->assertFalse($result->success);
        $this->assertEquals([
            'code' => '123',
            'message' => 'DB error'
        ], $result->error);
    }

    /**
     * Test: addTask returns ok but fetch() after insert gives false (no task found).
     */
    public function testAddTaskInsertedButNotFetched()
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
     */
    public function testGetAllTasksSuccess()
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
     */
    public function testGetAllTasksFail()
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
     */
    public function testFailFromStmtIncompleteErrorInfo()
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
     */
    public function testGetTaskByIDFound()
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
     */
    public function testGetTaskByIDNotFound()
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
     */
    public function testGetTaskByIDFail()
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
     */
    public function testGetTasksByPageSuccess()
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
     */
    public function testGetTasksByPageFail()
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
     */
    public function testGetTasksByPageZeroPage()
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
     */
    public function testGetTasksByPagePerPageZero()
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
     */
    public function testGetTasksByPageWithUserId()
    {
        $tasks = [['id' => 10, 'user_id' => 'u1']];
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchAll')->willReturn($tasks);

        // Call getTasksByPage
        $result = $this->taskQueries->getTasksByPage(1, 5, 'u1');

        $this->assertTrue($result->success);
        $this->assertEquals($tasks, $result->data);
    }

    /** ----------------- getTotalTasks ----------------- */
    /**
     * Test: getTotalTasks should return total number of tasks successfully.
     */
    public function testGetTotalTasksSuccess()
    {
        // Mock execute success and total count
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchColumn')->willReturn(5);

        // Call getTotalTasks
        $result = $this->taskQueries->getTotalTasks();

        // Assert success and correct total
        $this->assertTrue($result->success);
        $this->assertEquals(5, $result->data);
        $this->assertEquals(5, $result->affected);
    }

    /**
     * Test: getTotalTasks should return failure if execute fails.
     */
    public function testGetTotalTasksFail()
    {
        // Mock execute failure
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn(['code' => 'err']);

        // Call getTotalTasks
        $result = $this->taskQueries->getTotalTasks();

        // Assert failure
        $this->assertFalse($result->success);
    }

    /**
     * Test: getTotalTasks returns 0 when table is empty.
     */
    public function testGetTotalTasksZero()
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchColumn')->willReturn(0);

        // Call getTotalTasks
        $result = $this->taskQueries->getTotalTasks();

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->data);
        $this->assertEquals(0, $result->affected);
    }

    /** ----------------- markDone ----------------- */
    /**
     * Test: markDone should update task completion status successfully.
     */
    public function testMarkDoneSuccess()
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
     */
    public function testMarkDoneFail()
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
     */
    public function testMarkDoneFalse()
    {
        $task = ['id' => 2, 'title' => 'T2', 'is_done' => 0];
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn($task);

        // Call getTotalTasks
        $result = $this->taskQueries->markDone(2, false, 'testuserid');

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->data['is_done']);
    }

    /** ----------------- updateTask ----------------- */
    /**
     * Test: updateTask should modify task details successfully.
     */
    public function testUpdateTaskSuccess()
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
     */
    public function testUpdateTaskFail()
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
     */
    public function testUpdateTaskNoRowAffected()
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
     */
    public function testDeleteTaskSuccess()
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
     */
    public function testDeleteTaskFail()
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
     */
    public function testDeleteTaskNoRows()
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
