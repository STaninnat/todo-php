<?php

declare(strict_types=1);

namespace Tests\Integration\DB;

use App\DB\Database;
use App\DB\TaskQueries;
use PHPUnit\Framework\TestCase;
use PDO;

require_once __DIR__ . '/../bootstrap_db.php';
/**
 * Class TaskQueriesIntegrationTest
 *
 * Integration tests for the TaskQueries class.
 *
 * This test suite verifies:
 * - CRUD operations on the tasks table
 * - Pagination and task retrieval by user
 * - Task status updates (is_done)
 * - Total task count
 *
 * Uses a real database connection and requires bootstrap_db.php.
 *
 * @package Tests\Integration\DB
 */
final class TaskQueriesIntegrationTest extends TestCase
{
    /**
     * @var PDO PDO instance for integration testing
     */
    private PDO $pdo;

    /**
     * @var TaskQueries TaskQueries instance for testing
     */
    private TaskQueries $queries;

    /**
     * @var string Example user ID for testing
     */
    private string $userId = 'user_123';

    /**
     * Setup before each test.
     *
     * Establishes a real database connection, creates the tasks table,
     * and initializes TaskQueries instance.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbHost = $_ENV['DB_HOST'] ?? 'db_test';
        assert(is_string($dbHost));

        $dbPort = $_ENV['DB_PORT'] ?? 3306;
        assert(is_numeric($dbPort));
        $dbPort = (int) $dbPort;

        waitForDatabase($dbHost, $dbPort);

        $this->pdo = (new Database())->getConnection();
        $this->queries = new TaskQueries($this->pdo);

        // Recreate tasks table for clean test state
        $this->pdo->exec('DROP TABLE IF EXISTS tasks');
        $this->pdo->exec("
            CREATE TABLE tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                user_id VARCHAR(64) NOT NULL,
                is_done TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_is_done (is_done),
                INDEX idx_user_done (user_id, is_done)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    /**
     * Test adding a task and fetching it by ID.
     *
     * @return void
     */
    public function testAddAndGetTaskByID(): void
    {
        $result = $this->queries->addTask('Test title', 'Test description', $this->userId);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->data);
        $this->assertIsArray($result->data);

        /** @var array<string,mixed> $data */
        $data = $result->data;

        $this->assertSame('Test title', $data['title']);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);

        assert(isset($data['id']) && is_numeric($data['id']));
        $taskId = (int) $data['id'];

        // Fetch the task by ID
        $fetch = $this->queries->getTaskByID($taskId, $this->userId);
        $this->assertTrue($fetch->success);
        $this->assertNotNull($fetch->data);
        $this->assertIsArray($fetch->data);

        /** @var array<string,mixed> $fetchData */
        $fetchData = $fetch->data;

        assert(isset($fetchData['id']) && is_numeric($fetchData['id']));
        $this->assertSame($taskId, (int) $fetchData['id']);
        $this->assertSame($this->userId, $fetchData['user_id']);
    }

    /**
     * Test that getAllTasks returns tasks ordered by creation time.
     *
     * @return void
     */
    public function testGetAllTasksReturnsOrderedResults(): void
    {
        $this->queries->addTask('A', 'desc A', $this->userId);
        $this->queries->addTask('B', 'desc B', $this->userId);

        $result = $this->queries->getAllTasks();

        $this->assertTrue($result->success);
        $this->assertGreaterThanOrEqual(2, $result->affected);  // Ensure at least 2 tasks exist
        $this->assertIsArray($result->data);

        /** @var array<int, array<string,mixed>> $data */
        $data = $result->data;

        $this->assertArrayHasKey('created_at', $data[0]);   // Verify ordering key
    }

    /**
     * Test fetching tasks filtered by user ID.
     *
     * @return void
     */
    public function testGetTasksByUserID(): void
    {
        $this->queries->addTask('Task 1', 'desc 1', $this->userId);
        $this->queries->addTask('Task 2', 'desc 2', 'another_user');

        $result = $this->queries->getTasksByUserID($this->userId);

        $this->assertTrue($result->success);
        $this->assertIsArray($result->data);

        /** @var array<int, array<string,mixed>> $data */
        $data = $result->data;

        $this->assertCount(1, $data);   // Only tasks for $userId should be returned
        $this->assertSame($this->userId, $data[0]['user_id']);
    }

    /**
     * Test pagination of tasks using getTasksByPage.
     *
     * @return void
     */
    public function testGetTasksByPage(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->queries->addTask("Task {$i}", "desc {$i}", $this->userId);
        }

        $page1 = $this->queries->getTasksByPage(1, 10, $this->userId);
        $page2 = $this->queries->getTasksByPage(2, 10, $this->userId);

        $this->assertTrue($page1->success);
        $this->assertTrue($page2->success);
        $this->assertIsArray($page1->data);
        $this->assertIsArray($page2->data);

        /** @var array<int, array<string,mixed>> $data1 */
        $data1 = $page1->data;
        /** @var array<int, array<string,mixed>> $data2 */
        $data2 = $page2->data;

        $this->assertCount(10, $data1); // First page contains 10 tasks
        $this->assertCount(5, $data2);  // Second page contains remaining 5 tasks
    }

    /**
     * Test marking a task as done or undone.
     *
     * @return void
     */
    public function testMarkDoneUpdatesStatus(): void
    {
        $task = $this->queries->addTask('Task done', 'mark test', $this->userId);

        $this->assertNotNull($task->data);
        $this->assertIsArray($task->data);

        /** @var array<string,mixed> $data */
        $data = $task->data;

        assert(isset($data['id']) && is_numeric($data['id']));
        $id = (int) $data['id'];

        // Mark as done
        $update = $this->queries->markDone($id, true, $this->userId);
        $this->assertTrue($update->success);
        $this->assertNotNull($update->data);
        $this->assertIsArray($update->data);

        /** @var array<string,mixed> $updData */
        $updData = $update->data;
        assert(isset($updData['is_done']) && is_numeric($updData['is_done']));
        $this->assertSame(1, (int) $updData['is_done']);

        // Mark as not done
        $update2 = $this->queries->markDone($id, false, $this->userId);
        $this->assertTrue($update2->success);
        $this->assertNotNull($update2->data);
        $this->assertIsArray($update2->data);

        /** @var array<string,mixed> $updData2 */
        $updData2 = $update2->data;
        assert(isset($updData2['is_done']) && is_numeric($updData2['is_done']));
        $this->assertSame(0, (int) $updData2['is_done']);
    }

    /**
     * Test updating task fields.
     *
     * @return void
     */
    public function testUpdateTaskChangesFields(): void
    {
        $task = $this->queries->addTask('Old title', 'Old desc', $this->userId);

        $this->assertNotNull($task->data);
        $this->assertIsArray($task->data);

        /** @var array<string,mixed> $data */
        $data = $task->data;

        assert(isset($data['id']) && is_numeric($data['id']));
        $id = (int) $data['id'];

        $update = $this->queries->updateTask($id, 'New title', 'New desc', true, $this->userId);
        $this->assertTrue($update->success);
        $this->assertNotNull($update->data);
        $this->assertIsArray($update->data);

        /** @var array<string,mixed> $updData */
        $updData = $update->data;

        $this->assertSame('New title', $updData['title']);
        $this->assertSame('New desc', $updData['description']);

        assert(isset($updData['is_done']) && is_numeric($updData['is_done']));
        $this->assertSame(1, (int) $updData['is_done']);
        $this->assertNotEmpty($updData['updated_at']); // Ensure updated_at timestamp is set
    }

    /**
     * Test deleting a task.
     *
     * @return void
     */
    public function testDeleteTaskRemovesIt(): void
    {
        $task = $this->queries->addTask('Delete me', 'temp', $this->userId);

        $this->assertNotNull($task->data);
        $this->assertIsArray($task->data);

        /** @var array<string,mixed> $data */
        $data = $task->data;

        assert(isset($data['id']) && is_numeric($data['id']));
        $id = (int) $data['id'];

        // Delete the task
        $delete = $this->queries->deleteTask($id, $this->userId);

        $this->assertTrue($delete->success);
        $this->assertSame(1, $delete->affected);

        // Verify the task no longer exists
        $fetch = $this->queries->getTaskByID($id, $this->userId);

        $this->assertTrue($fetch->success);
        $this->assertNull($fetch->data);
    }



    /**
     * Test updating a task with an invalid ID should return null data.
     *
     * @return void
     */
    public function testUpdateTaskFailureInvalidID(): void
    {
        // Try updating a non-existent task.
        $result = $this->queries->updateTask(9999, 'New Title', 'New Desc', true, $this->userId);

        // update succeeded but row was not changed -> getTaskByID return data null
        $this->assertTrue($result->success);
        $this->assertNull($result->data);
    }

    /**
     * Cleanup after each test.
     *
     * Drops the tasks table to avoid side effects.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS tasks');
        parent::tearDown();
    }
}
