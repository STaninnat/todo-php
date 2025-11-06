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
    private TaskController $controller;
    private string $userId = 'user_integ';

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/../../../bootstrap_db.php';

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
                description TEXT NOT NULL,
                user_id VARCHAR(64) NOT NULL,
                is_done TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->controller = new TaskController(
            new AddTaskService($this->queries),
            new DeleteTaskService($this->queries),
            new UpdateTaskService($this->queries),
            new MarkDoneTaskService($this->queries),
            new GetTasksService($this->queries)
        );
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS tasks');
        parent::tearDown();
    }

    private function makeRequestFromBody(array $body, string $method = 'POST'): Request
    {
        $json = json_encode($body);
        if ($json === false) {
            throw new RuntimeException('Failed to encode request body to JSON.');
        }

        return new Request($method, '/', [], $json);
    }

    public function testAddTaskSuccess(): void
    {
        $req = $this->makeRequestFromBody([
            'title' => 'Integration Add',
            'description' => 'Desc add',
            'user_id' => $this->userId,
        ]);

        $res = $this->controller->addTask($req, true);

        $this->assertIsArray($res);
        $this->assertTrue($res['success']);
        $this->assertSame('success', $res['type']);
        $this->assertSame('Task added successfully', $res['message']);
        $this->assertArrayHasKey('data', $res);
        $this->assertArrayHasKey('task', $res['data']);
        $this->assertArrayHasKey('id', $res['data']['task']);

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM tasks');
        $this->assertNotFalse($stmt);
        $count = (int) $stmt->fetchColumn();
        $this->assertSame(1, $count);
    }

    public function testAddTaskMissingTitleThrows(): void
    {
        $req = $this->makeRequestFromBody([
            'description' => 'no title',
            'user_id' => $this->userId,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task title is required.');
        $this->controller->addTask($req, true);
    }

    public function testAddTaskWithLongTitleFails(): void
    {
        $longTitle = str_repeat('A', 300); // more than VARCHAR(255)
        $req = $this->makeRequestFromBody([
            'title' => $longTitle,
            'description' => 'Too long',
            'user_id' => $this->userId,
        ]);

        $this->expectException(RuntimeException::class);
        $this->controller->addTask($req, true);
    }

    public function testGetTasksReturnsUserTasks(): void
    {
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id)
            VALUES
            ('T1', 'D1', '{$this->userId}'),
            ('T2', 'D2', '{$this->userId}'),
            ('Other', 'D3', 'other_user');
        ");

        $req = $this->makeRequestFromBody(['user_id' => $this->userId], 'GET');
        $res = $this->controller->getTasks($req, true);

        $this->assertTrue($res['success']);
        $this->assertSame('Task retrieved successfully', $res['message']);
        $this->assertArrayHasKey('data', $res);
        $this->assertArrayHasKey('task', $res['data']);
        $this->assertIsArray($res['data']['task']);
        $this->assertCount(2, $res['data']['task']);
        foreach ($res['data']['task'] as $t) {
            $this->assertSame($this->userId, $t['user_id']);
        }
    }

    public function testTasksAreIsolatedBetweenUsers(): void
    {
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id)
            VALUES
            ('T1', 'D1', '{$this->userId}'),
            ('T2', 'D2', 'another_user');
        ");

        $req = $this->makeRequestFromBody(['user_id' => $this->userId], 'GET');
        $res = $this->controller->getTasks($req, true);

        $this->assertCount(1, $res['data']['task']);
        $this->assertSame($this->userId, $res['data']['task'][0]['user_id']);
    }

    public function testTasksReturnedInChronologicalOrder(): void
    {
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id, created_at)
            VALUES
            ('First', 'D1', '{$this->userId}', '2024-01-01 00:00:00'),
            ('Second', 'D2', '{$this->userId}', '2024-01-02 00:00:00');
        ");

        $req = $this->makeRequestFromBody(['user_id' => $this->userId], 'GET');
        $res = $this->controller->getTasks($req, true);

        $this->assertCount(2, $res['data']['task']);
        $this->assertSame('First', $res['data']['task'][0]['title']);
        $this->assertSame('Second', $res['data']['task'][1]['title']);
    }

    public function testUpdateTaskSuccess(): void
    {
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id)
            VALUES ('Old', 'OldD', '{$this->userId}');
        ");
        $id = (int)$this->pdo->lastInsertId();

        $req = $this->makeRequestFromBody([
            'id' => $id,
            'title' => 'New title',
            'description' => 'New desc',
            'user_id' => $this->userId,
            'is_done' => true,
        ]);

        $res = $this->controller->updateTask($req, true);

        $this->assertTrue($res['success']);
        $this->assertSame('Task updated successfully', $res['message']);
        $this->assertSame('New title', $res['data']['task']['title']);
        $this->assertSame('New desc', $res['data']['task']['description']);
        $this->assertSame(1, (int)$res['data']['task']['is_done']);
    }

    public function testUpdateTaskNotFoundThrows(): void
    {
        $req = $this->makeRequestFromBody([
            'id' => 99999,
            'title' => 'Does not exist',
            'description' => 'x',
            'user_id' => $this->userId,
            'is_done' => false,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No task found.');
        $this->controller->updateTask($req, true);
    }

    public function testMarkDoneTaskSuccess(): void
    {
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id, is_done)
            VALUES ('To mark', 'desc', '{$this->userId}', 0);
        ");
        $id = (int)$this->pdo->lastInsertId();

        $req = $this->makeRequestFromBody([
            'id' => $id,
            'user_id' => $this->userId,
            'is_done' => true,
        ]);

        $res = $this->controller->markDoneTask($req, true);

        $this->assertTrue($res['success']);
        $this->assertSame('Task status updated successfully', $res['message']);
        $this->assertSame(1, (int)$res['data']['task']['is_done']);
    }

    public function testMarkDoneTaskNotFoundThrows(): void
    {
        $req = $this->makeRequestFromBody([
            'id' => 54321,
            'user_id' => $this->userId,
            'is_done' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No task found.');
        $this->controller->markDoneTask($req, true);
    }

    public function testDeleteTaskSuccess(): void
    {
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id)
            VALUES ('To delete', 'desc', '{$this->userId}');
        ");
        $id = (int)$this->pdo->lastInsertId();

        $req = $this->makeRequestFromBody([
            'id' => $id,
            'user_id' => $this->userId,
        ]);

        $res = $this->controller->deleteTask($req, true);

        $this->assertTrue($res['success']);
        $this->assertSame('Task deleted successfully', $res['message']);
        $this->assertSame($id, $res['data']['id']);

        $fetch = $this->queries->getTaskByID($id, $this->userId);
        $this->assertTrue($fetch->success);
        $this->assertNull($fetch->data);
    }

    public function testDeleteTaskNotFoundThrows(): void
    {
        $req = $this->makeRequestFromBody([
            'id' => 77777,
            'user_id' => $this->userId,
        ]);

        $this->expectException(RuntimeException::class);
        $this->controller->deleteTask($req, true);
    }

    public function testFullTaskLifecycle(): void
    {
        // Add
        $addReq = $this->makeRequestFromBody([
            'title' => 'Lifecycle',
            'description' => 'Test full flow',
            'user_id' => $this->userId,
        ]);
        $addRes = $this->controller->addTask($addReq, true);
        $this->assertTrue($addRes['success']);
        $taskId = $addRes['data']['task']['id'];

        // Update
        $updateReq = $this->makeRequestFromBody([
            'id' => $taskId,
            'title' => 'Lifecycle updated',
            'description' => 'Updated desc',
            'user_id' => $this->userId,
            'is_done' => false,
        ]);
        $updateRes = $this->controller->updateTask($updateReq, true);
        $this->assertTrue($updateRes['success']);
        $this->assertSame('Lifecycle updated', $updateRes['data']['task']['title']);

        // Mark done
        $doneReq = $this->makeRequestFromBody([
            'id' => $taskId,
            'user_id' => $this->userId,
            'is_done' => true,
        ]);
        $doneRes = $this->controller->markDoneTask($doneReq, true);
        $this->assertTrue($doneRes['success']);
        $this->assertSame(1, (int)$doneRes['data']['task']['is_done']);

        // Get
        $getReq = $this->makeRequestFromBody(['user_id' => $this->userId], 'GET');
        $getRes = $this->controller->getTasks($getReq, true);
        $this->assertCount(1, $getRes['data']['task']);
        $this->assertSame('Lifecycle updated', $getRes['data']['task'][0]['title']);
        $this->assertSame(1, (int)$getRes['data']['task'][0]['is_done']);

        // Delete
        $deleteReq = $this->makeRequestFromBody([
            'id' => $taskId,
            'user_id' => $this->userId,
        ]);
        $deleteRes = $this->controller->deleteTask($deleteReq, true);
        $this->assertTrue($deleteRes['success']);

        // Verify deleted
        $fetch = $this->queries->getTaskByID($taskId, $this->userId);
        $this->assertNull($fetch->data);
    }
}
