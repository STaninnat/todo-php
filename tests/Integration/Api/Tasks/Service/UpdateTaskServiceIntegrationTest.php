<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Tasks\Service;

use App\Api\Request;
use App\Api\Tasks\Service\UpdateTaskService;
use App\DB\Database;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use PDO;

final class UpdateTaskServiceIntegrationTest extends TestCase
{
    /**
     * @var PDO PDO instance for integration testing
     */
    private PDO $pdo;

    /**
     * @var TaskQueries TaskQueries instance for testing
     */
    private TaskQueries $queries;

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/../../../bootstrap_db.php';

        $dbHost = $_ENV['DB_HOST'] ?? 'db_test';
        assert(is_string($dbHost));

        $dbPort = $_ENV['DB_PORT'] ?? 3306;
        assert(is_numeric($dbPort));
        $dbPort = (int)$dbPort;

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
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS tasks');
    }

    /**
     * @return array<string, int|string>
     */
    private function createSampleTask(): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO tasks (title, description, user_id) VALUES (?, ?, ?)');
        $stmt->execute(['Old Title', 'Old description', 'user_123']);
        $id = (int)$this->pdo->lastInsertId();

        return [
            'id' => $id,
            'title' => 'Old Title',
            'description' => 'Old description',
            'user_id' => 'user_123',
        ];
    }

    /**
     * @param array<string, mixed> $body
     */
    private function makeRequest(array $body): Request
    {
        $json = json_encode($body);
        if ($json === false) {
            throw new RuntimeException('Failed to encode request body to JSON.');
        }

        return new Request('PUT', '/tasks/update', [], $json);
    }

    public function testUpdateTaskSuccessfully(): void
    {
        $task = $this->createSampleTask();
        $service = new UpdateTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => $task['id'],
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'is_done' => true,
            'user_id' => $task['user_id'],
        ]);

        $result = $service->execute($req);

        assert(isset($result['task']['is_done']) && is_numeric($result['task']['is_done']));
        $this->assertArrayHasKey('task', $result);
        $this->assertSame('Updated Title', $result['task']['title']);
        $this->assertSame('Updated description', $result['task']['description']);
        $this->assertSame(1, (int)$result['task']['is_done']);
        $this->assertSame(1, $result['totalPages']);
    }

    public function testUpdateTaskWithoutTitleThrowsException(): void
    {
        $task = $this->createSampleTask();
        $service = new UpdateTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => $task['id'],
            'is_done' => true,
            'user_id' => $task['user_id'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task title is required.');
        $service->execute($req);
    }

    public function testUpdateTaskWithoutUserIdThrowsException(): void
    {
        $task = $this->createSampleTask();
        $service = new UpdateTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => $task['id'],
            'title' => 'No User',
            'is_done' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required.');
        $service->execute($req);
    }

    public function testUpdateTaskWithInvalidTypesThrowsException(): void
    {
        $task = $this->createSampleTask();
        $service = new UpdateTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => 'invalid',
            'title' => ['array'],
            'is_done' => 'wrong',
            'user_id' => $task['user_id'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $service->execute($req);
    }

    public function testUpdateTaskFailsWhenTaskNotFound(): void
    {
        $service = new UpdateTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => 9999,
            'title' => 'Nonexistent',
            'description' => 'Should fail',
            'is_done' => false,
            'user_id' => 'user_123',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No task found.');
        $service->execute($req);
    }

    public function testUpdateTaskFailsOnDatabaseError(): void
    {
        $task = $this->createSampleTask();
        $service = new UpdateTaskService($this->queries);

        // title longer than 255 → SQL error
        $longTitle = str_repeat('x', 300);
        $req = $this->makeRequest([
            'id' => $task['id'],
            'title' => $longTitle,
            'description' => 'bad data',
            'is_done' => true,
            'user_id' => $task['user_id'],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('update task');
        $service->execute($req);
    }
}
