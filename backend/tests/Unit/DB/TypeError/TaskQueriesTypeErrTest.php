<?php

declare(strict_types=1);

namespace Tests\Unit\DB\TypeError;

use PHPUnit\Framework\TestCase;
use App\DB\TaskQueries;
use PDO;

/**
 * Class TaskQueriesTypeErrTest
 *
 * Unit tests for TaskQueries to ensure strict typing enforcement.
 * Tests that TypeError is thrown when methods receive invalid argument types.
 *
 * @package Tests\Unit\DB\TypeError
 */
class TaskQueriesTypeErrTest extends TestCase
{
    /**
     * @var TaskQueries TaskQueries instance used for testing
     */
    private TaskQueries $taskQueries;

    /**
     * Set up TaskQueries instance with a mocked PDO before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Mock PDO
        $pdo = $this->createMock(PDO::class);
        $this->taskQueries = new TaskQueries($pdo);
    }

    /**
     * Test addTask() throws TypeError when title is not a string.
     *
     * @return void
     */
    public function testAddTaskInvalidTypes(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the title as an int instead of a string will cause a TypeError.
        $this->taskQueries->addTask(123, 'desc', 'user');
    }

    /**
     * Test getTaskByID() throws TypeError when id is not an int.
     *
     * @return void
     */
    public function testGetTaskByIDInvalidTypes(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the id as a string instead of an int will cause a TypeError.
        $this->taskQueries->getTaskByID('not-int', 'user');
    }

    /**
     * Test getTasksByPage() throws TypeError when page is not an int.
     *
     * @return void
     */
    public function testGetTasksByPageInvalidTypes(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the page as a string instead of an int will cause a TypeError.
        $this->taskQueries->getTasksByPage('page');
    }

    /**
     * Test markDone() throws TypeError when id or isDone is not the expected type.
     *
     * @return void
     */
    public function testMarkDoneInvalidTypes(): void
    {
        $this->expectException(\TypeError::class);

        // Sending id, isDone as a string instead of int and bool will cause a TypeError.
        $this->taskQueries->markDone('id', 'true', 'user');
    }

    /**
     * Test updateTask() throws TypeError when arguments are of invalid types.
     *
     * @return void
     */
    public function testUpdateTaskInvalidTypes(): void
    {
        $this->expectException(\TypeError::class);

        // id, title, description, isDone, userId must match the method signature types.
        $this->taskQueries->updateTask('id', 123, [], 'yes', 456);
    }

    /**
     * Test deleteTask() throws TypeError when id is not an int.
     *
     * @return void
     */
    public function testDeleteTaskInvalidTypes(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the id as a string instead of an int will cause a TypeError.
        $this->taskQueries->deleteTask('id', 'user');
    }

    /**
     * Test: countTasksByUserId() throws TypeError when userId is not string.
     *
     * @return void
     */
    public function testCountTasksByUserIdInvalidTypes(): void
    {
        $this->expectException(\TypeError::class);

        $this->taskQueries->countTasksByUserId(12345);
    }

    /**
     * Test: deleteTasks() throws TypeError when input types are invalid.
     *
     * @return void
     */
    public function testDeleteTasksInvalidTypes(): void
    {
        $this->expectException(\TypeError::class);

        // id list must be array, and userId string
        $this->taskQueries->deleteTasks('not-array', 123);
    }

    /**
     * Test: markTasksDone() throws TypeError when input types are invalid.
     *
     * @return void
     */
    public function testMarkTasksDoneInvalidTypes(): void
    {
        $this->expectException(\TypeError::class);

        // id list must be array, isDone bool, and userId string
        $this->taskQueries->markTasksDone('not-array', 'not-bool', 123);
    }
}

