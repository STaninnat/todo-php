<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Tasks\Service;

use App\Api\Request;
use App\Api\Tasks\Service\AddTaskService;
use App\DB\Database;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use PDO;

final class AddTaskServiceIntegrationTest extends TestCase
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

        // fresh tasks table
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
     * @param array<string, mixed> $body
     */
    private function makeRequest(array $body): Request
    {
        $json = json_encode($body);
        if ($json === false) {
            throw new RuntimeException('Failed to encode request body to JSON.');
        }

        return new Request('POST', '/tasks', [], $json);
    }

    public function testAddTaskSuccessfully(): void
    {
        $service = new AddTaskService($this->queries);

        $req = $this->makeRequest([
            'title' => 'Integration Task',
            'description' => 'This is a sample task for integration test.',
            'user_id' => 'user_123',
        ]);

        $result = $service->execute($req);

        $this->assertArrayHasKey('task', $result);
        $this->assertArrayHasKey('totalPages', $result);
        $this->assertSame(1, $result['totalPages']);

        $task = $result['task'];
        $this->assertSame('Integration Task', $task['title']);
        $this->assertSame('This is a sample task for integration test.', $task['description']);
        $this->assertSame('user_123', $task['user_id']);

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM tasks');
        if ($stmt === false) {
            throw new RuntimeException('Failed to count tasks.');
        }

        $count = (int) $stmt->fetchColumn();
        $this->assertSame(1, $count);
    }

    public function testAddTaskWithoutTitleThrowsException(): void
    {
        $service = new AddTaskService($this->queries);

        $req = $this->makeRequest([
            'description' => 'Missing title field',
            'user_id' => 'user_123',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task title is required.');
        $service->execute($req);
    }

    public function testAddTaskWithoutUserIdThrowsException(): void
    {
        $service = new AddTaskService($this->queries);

        $req = $this->makeRequest([
            'title' => 'Task without user_id',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required.');
        $service->execute($req);
    }

    public function testAddTaskWithInvalidTypesThrowsException(): void
    {
        $service = new AddTaskService($this->queries);

        // invalid type: title=array, user_id=int
        $req = $this->makeRequest([
            'title' => ['wrong'],
            'user_id' => 123,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $service->execute($req);
    }

    public function testAddTaskFailsOnDatabaseError(): void
    {
        $service = new AddTaskService($this->queries);

        // create title longer than 255 chars -> DB insert should fail
        $longTitle = str_repeat('x', 300);
        $req = $this->makeRequest([
            'title' => $longTitle,
            'user_id' => 'user_999',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('add task');
        $service->execute($req);
    }

    public function testPaginationIncreasesWithMultipleTasks(): void
    {
        $service = new AddTaskService($this->queries);

        // Insert 25 tasks (perPage = 10 => expect totalPages = 3)
        for ($i = 1; $i <= 25; $i++) {
            $req = $this->makeRequest([
                'title' => "Task {$i}",
                'user_id' => 'user_abc',
            ]);

            $result = $service->execute($req);
        }

        $this->assertSame(3, $result['totalPages']);
    }
}
