<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Tasks\Controller;

use App\Api\Request;
use App\Api\Tasks\Controller\TaskController;
use App\Api\Tasks\Service\AddTaskService;
use App\Api\Tasks\Service\DeleteTaskService;
use App\Api\Tasks\Service\GetTasksService;
use App\Api\Tasks\Service\MarkDoneTaskService;
use App\Api\Tasks\Service\UpdateTaskService;
use App\DB\Database;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PDO;
use RuntimeException;

require_once __DIR__ . '/../../../bootstrap_db.php';

/**
 * Class TaskControllerIntegrationTest
 *
 * Integration tests for the TaskController class.
 *
 * This test suite verifies the controller’s end-to-end behavior against
 * a real database connection (via PDO and TaskQueries).
 *
 * Covered scenarios:
 * - Successful task lifecycle operations (add, update, mark done, delete, get)
 * - Proper validation for required fields and data constraints
 * - Correct data persistence and retrieval from database
 * - Exception propagation on invalid or failed operations
 *
 * @package Tests\Integration\Api\Tasks\Controller
 */
final class TaskControllerIntegrationTest extends TestCase
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
     * @var TaskController Controller under test 
     */
    private TaskController $controller;

    /** 
     * @var string Simulated user ID used in integration tests 
     */
    private string $userId = 'user_integ';

    /**
     * Sets up the integration test environment.
     *
     * - Waits for DB to be ready
     * - Creates schema for `tasks` table
     * - Instantiates TaskController with real service dependencies
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

        // Instantiate controller with actual service layers
        $this->controller = new TaskController(
            new AddTaskService($this->queries),
            new DeleteTaskService($this->queries),
            new UpdateTaskService($this->queries),
            new MarkDoneTaskService($this->queries),
            new GetTasksService($this->queries)
        );
    }

    /**
     * Cleans up after each test by dropping the tasks table.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS tasks');
        parent::tearDown();
    }

    /**
     * Helper method to build a Request object from an associative array.
     *
     * @param array<string, mixed> $body Request body data
     * @param string $method HTTP method (default: POST)
     * 
     * @return Request
     */
    private function makeRequestFromBody(array $body, string $method = 'POST', ?string $userId = null): Request
    {
        $json = json_encode($body);
        if ($json === false) {
            throw new RuntimeException('Failed to encode request body to JSON.');
        }

        // Create Request with encoded JSON payload
        $req = new Request($method, '/', [], $json);
        if ($userId !== null) {
            $req->auth = ['id' => $userId];
        }
        return $req;
    }

    /**
     * Test successful task creation through controller and DB persistence.
     *
     * Ensures returned data matches inserted record and DB contains 1 row.
     *
     * @return void
     */
    public function testAddTaskSuccess(): void
    {
        $req = $this->makeRequestFromBody([
            'title' => 'Integration Add',
            'description' => 'Desc add',
        ], 'POST', $this->userId);

        /** @var array{
         *   success: bool,
         *   type: string,
         *   message: string,
         *   data: array{task: array{id: int|string}}
         * } $res
         */
        $res = $this->controller->addTask($req, true);

        // Assertions on response structure 
        $this->assertTrue($res['success']);
        $this->assertSame('success', $res['type']);
        $this->assertSame('Task added successfully', $res['message']);
        $this->assertArrayHasKey('data', $res);
        $this->assertArrayHasKey('task', $res['data']);
        $this->assertArrayHasKey('id', $res['data']['task']);

        // Verify persistence in database 
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM tasks');
        $this->assertNotFalse($stmt);
        $count = (int) $stmt->fetchColumn();
        $this->assertSame(1, $count);
    }

    /**
     * Test that missing title field triggers validation error.
     *
     * @return void
     */
    public function testAddTaskMissingTitleThrows(): void
    {
        $req = $this->makeRequestFromBody([
            'description' => 'no title',
        ], 'POST', $this->userId);

        // Expect validation failure due to missing title
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task title is required.');
        $this->controller->addTask($req, true);
    }

    /**
     * Test that excessively long title causes database error.
     *
     * @return void
     */
    public function testAddTaskWithLongTitleFails(): void
    {
        $longTitle = str_repeat('A', 300); // more than VARCHAR(255)
        $req = $this->makeRequestFromBody([
            'title' => $longTitle,
            'description' => 'Too long',
        ], 'POST', $this->userId);

        $this->expectException(RuntimeException::class);
        $this->controller->addTask($req, true);
    }

    /**
     * Test retrieval of all tasks belonging to the given user.
     *
     * @return void
     */
    public function testGetTasksReturnsUserTasks(): void
    {
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id)
            VALUES
            ('T1', 'D1', '{$this->userId}'),
            ('T2', 'D2', '{$this->userId}'),
            ('Other', 'D3', 'other_user');
        ");

        $req = $this->makeRequestFromBody([], 'GET', $this->userId);

        /** @var array{
         *   success: bool,
         *   message: string,
         *   data: array{task: list<array{user_id: string}>}
         * } $res
         */
        $res = $this->controller->getTasks($req, true);

        // Assertions 
        $this->assertTrue($res['success']);
        $this->assertSame('Task retrieved successfully', $res['message']);
        $this->assertCount(2, $res['data']['task']);

        // Ensure all tasks belong to current user
        foreach ($res['data']['task'] as $t) {
            $this->assertArrayNotHasKey('user_id', $t);
        }

        $titles = array_column($res['data']['task'], 'title');
        $this->assertContains('T1', $titles);
        $this->assertContains('T2', $titles);
        $this->assertNotContains('Other', $titles);
    }

    /**
     * Test that tasks between users remain isolated.
     *
     * @return void
     */
    public function testTasksAreIsolatedBetweenUsers(): void
    {
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id)
            VALUES
            ('T1', 'D1', '{$this->userId}'),
            ('T2', 'D2', 'another_user');
        ");

        $req = $this->makeRequestFromBody([], 'GET', $this->userId);

        /** @var array{
         *   success: bool,
         *   data: array{task: list<array{user_id: string}>}
         * } $res
         */
        $res = $this->controller->getTasks($req, true);

        // Expect only tasks belonging to current user
        $this->assertCount(1, $res['data']['task']);
        $this->assertArrayNotHasKey('user_id', $res['data']['task'][0]);
        $this->assertSame('T1', $res['data']['task'][0]['title']);
    }

    /**
     * Test that retrieved tasks are ordered by updated_at (descending) and is_done (ascending).
     *
     * @return void
     */
    public function testTasksReturnedInCorrectOrder(): void
    {
        $this->queries->addTask('First', 'D1', $this->userId);
        sleep(1);
        $this->queries->addTask('Second', 'D2', $this->userId);

        $req = $this->makeRequestFromBody([], 'GET', $this->userId);

        /** @var array{
         *   success: bool,
         *   data: array{task: list<array{title: string}>}
         * } $res
         */
        $res = $this->controller->getTasks($req, true);

        // Ensure order (Second inserted last, so updated_at is later -> appears first)
        $this->assertTrue($res['success']);
        $this->assertCount(2, $res['data']['task']);
        $this->assertSame('Second', $res['data']['task'][0]['title']);
        $this->assertSame('First', $res['data']['task'][1]['title']);
    }

    /**
     * Test successful task update flow.
     *
     * @return void
     */
    public function testUpdateTaskSuccess(): void
    {
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id)
            VALUES ('Old', 'OldD', '{$this->userId}');
        ");
        $id = (int) $this->pdo->lastInsertId();

        $req = $this->makeRequestFromBody([
            'id' => $id,
            'title' => 'New title',
            'description' => 'New desc',
            'is_done' => true,
        ], 'POST', $this->userId);

        /** @var array{
         *   success: bool,
         *   message: string,
         *   data: array{task: array{title: string, description: string, is_done: int|bool}}
         * } $res
         */
        $res = $this->controller->updateTask($req, true);

        // Assertions on updated data
        $this->assertTrue($res['success']);
        $this->assertSame('Task updated successfully', $res['message']);
        $this->assertSame('New title', $res['data']['task']['title']);
        $this->assertSame('New desc', $res['data']['task']['description']);
        $this->assertSame(1, (int) $res['data']['task']['is_done']);
    }

    /**
     * Test update behavior when the task does not exist.
     *
     * @return void
     */
    public function testUpdateTaskNotFoundThrows(): void
    {
        $req = $this->makeRequestFromBody([
            'id' => 99999,
            'title' => 'Does not exist',
            'description' => 'x',
            'is_done' => false,
        ], 'POST', $this->userId);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No task found.');
        $this->controller->updateTask($req, true);
    }

    /**
     * Test marking a task as done successfully.
     *
     * @return void
     */
    public function testMarkDoneTaskSuccess(): void
    {
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id, is_done)
            VALUES ('To mark', 'desc', '{$this->userId}', 0);
        ");
        $id = (int) $this->pdo->lastInsertId();

        $req = $this->makeRequestFromBody([
            'id' => $id,
            'is_done' => true,
        ], 'POST', $this->userId);

        /** @var array{
         *   success: bool,
         *   message: string,
         *   data: array{task: array{is_done: int|bool}}
         * } $res
         */
        $res = $this->controller->markDoneTask($req, true);

        $this->assertTrue($res['success']);
        $this->assertSame('Task status updated successfully', $res['message']);
        $this->assertSame(1, (int) $res['data']['task']['is_done']);
    }

    /**
     * Test that marking a non-existent task throws exception.
     *
     * @return void
     */
    public function testMarkDoneTaskNotFoundThrows(): void
    {
        $req = $this->makeRequestFromBody([
            'id' => 54321,
            'is_done' => true,
        ], 'POST', $this->userId);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No task found.');
        $this->controller->markDoneTask($req, true);
    }

    /**
     * Test successful task deletion and database cleanup.
     *
     * @return void
     */
    public function testDeleteTaskSuccess(): void
    {
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id)
            VALUES ('To delete', 'desc', '{$this->userId}');
        ");
        $id = (int) $this->pdo->lastInsertId();

        $req = $this->makeRequestFromBody([
            'id' => $id,
        ], 'POST', $this->userId);

        /** @var array{
         *   success: bool,
         *   message: string,
         *   data: array{id: int}
         * } $res
         */
        $res = $this->controller->deleteTask($req, true);

        // Verify deletion response
        $this->assertTrue($res['success']);
        $this->assertSame('Task deleted successfully', $res['message']);
        $this->assertSame($id, $res['data']['id']);

        // Verify record no longer exists
        $fetch = $this->queries->getTaskByID((int) $id, $this->userId);
        $this->assertTrue($fetch->success);
        $this->assertNull($fetch->data);
    }

    /**
     * Test that deleting a non-existent task throws exception.
     *
     * @return void
     */
    public function testDeleteTaskNotFoundThrows(): void
    {
        $req = $this->makeRequestFromBody([
            'id' => 77777,
        ], 'POST', $this->userId);

        $this->expectException(RuntimeException::class);
        $this->controller->deleteTask($req, true);
    }

    /**
     * Full integration test covering entire task lifecycle.
     *
     * Sequence tested:
     * 1. Add → 2. Update → 3. Mark Done → 4. Get → 5. Delete
     * Ensures DB state transitions and responses are consistent.
     *
     * @return void
     */
    public function testFullTaskLifecycle(): void
    {
        // Add
        $addReq = $this->makeRequestFromBody([
            'title' => 'Lifecycle',
            'description' => 'Test full flow',
        ], 'POST', $this->userId);

        /** @var array{
         *   success: bool,
         *   data: array{task: array{id: int|string}}
         * } $addRes
         */
        $addRes = $this->controller->addTask($addReq, true);
        $this->assertTrue($addRes['success']);
        $taskId = $addRes['data']['task']['id'];

        // Update
        $updateReq = $this->makeRequestFromBody([
            'id' => $taskId,
            'title' => 'Lifecycle updated',
            'description' => 'Updated desc',
            'is_done' => false,
        ], 'POST', $this->userId);

        /** @var array{
         *   success: bool,
         *   data: array{task: array{title: string}}
         * } $updateRes
         */
        $updateRes = $this->controller->updateTask($updateReq, true);
        $this->assertTrue($updateRes['success']);
        $this->assertSame('Lifecycle updated', $updateRes['data']['task']['title']);

        // Mark done
        $doneReq = $this->makeRequestFromBody([
            'id' => $taskId,
            'is_done' => true,
        ], 'POST', $this->userId);

        /** @var array{
         *   success: bool,
         *   data: array{task: array{is_done: int|bool}}
         * } $doneRes
         */
        $doneRes = $this->controller->markDoneTask($doneReq, true);
        $this->assertTrue($doneRes['success']);
        $this->assertSame(1, (int) $doneRes['data']['task']['is_done']);

        // Get
        $getReq = $this->makeRequestFromBody([], 'GET', $this->userId);

        /** @var array{
         *   success: bool,
         *   data: array{task: list<array{title: string, is_done: int|bool}>}
         * } $getRes
         */
        $getRes = $this->controller->getTasks($getReq, true);
        $this->assertCount(1, $getRes['data']['task']);
        $this->assertSame('Lifecycle updated', $getRes['data']['task'][0]['title']);
        $this->assertSame(1, (int) $getRes['data']['task'][0]['is_done']);

        // Delete
        $deleteReq = $this->makeRequestFromBody([
            'id' => $taskId,
        ], 'POST', $this->userId);

        /** @var array{success: bool} $deleteRes */
        $deleteRes = $this->controller->deleteTask($deleteReq, true);
        $this->assertTrue($deleteRes['success']);

        // Verify deleted
        $fetch = $this->queries->getTaskByID((int) $taskId, $this->userId);
        $this->assertNull($fetch->data);
    }
}
