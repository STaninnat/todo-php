<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Tasks\Service;

use App\Api\Request;
use App\Api\Tasks\Service\DeleteTaskService;
use App\DB\Database;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use PDO;

final class DeleteTaskServiceIntegrationTest extends TestCase
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

        return new Request('DELETE', '/tasks', [], $json);
    }

    public function testDeleteTaskSuccessfully(): void
    {
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id)
            VALUES ('Test Task', 'To be deleted', 'user_123')
        ");
        $taskId = (int)$this->pdo->lastInsertId();

        $service = new DeleteTaskService($this->queries);
        $req = $this->makeRequest([
            'id' => $taskId,
            'user_id' => 'user_123',
        ]);

        $result = $service->execute($req);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('totalPages', $result);
        $this->assertSame($taskId, $result['id']);

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM tasks');
        if ($stmt === false) {
            throw new RuntimeException('Failed to count tasks.');
        }
        $count = (int)$stmt->fetchColumn();
        $this->assertSame(0, $count);
    }

    public function testDeleteTaskWithoutIdThrowsException(): void
    {
        $service = new DeleteTaskService($this->queries);
        $req = $this->makeRequest(['user_id' => 'user_123']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task ID must be a numeric string.');
        $service->execute($req);
    }

    public function testDeleteTaskWithoutUserIdThrowsException(): void
    {
        $service = new DeleteTaskService($this->queries);
        $req = $this->makeRequest(['id' => 1]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required.');
        $service->execute($req);
    }

    public function testDeleteNonExistentTaskThrowsRuntimeException(): void
    {
        $service = new DeleteTaskService($this->queries);
        $req = $this->makeRequest([
            'id' => 999,
            'user_id' => 'ghost_user',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('delete task');
        $service->execute($req);
    }

    public function testPaginationDecreasesAfterDeletion(): void
    {
        // insert 11 tasks → expect totalPages = 2 (perPage=10)
        for ($i = 1; $i <= 11; $i++) {
            $this->pdo->exec("
                INSERT INTO tasks (title, description, user_id)
                VALUES ('Task {$i}', 'desc', 'user_abc')
            ");
        }

        $stmt = $this->pdo->query('SELECT id FROM tasks LIMIT 1');
        if ($stmt === false) {
            throw new RuntimeException('Failed to fetch task id.');
        }
        $taskId = (int)$stmt->fetchColumn();

        $service = new DeleteTaskService($this->queries);
        $req = $this->makeRequest(['id' => $taskId, 'user_id' => 'user_abc']);
        $result = $service->execute($req);

        $this->assertSame(1, $result['totalPages']);

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM tasks');
        if ($stmt === false) {
            throw new RuntimeException('Failed to count tasks.');
        }

        $count = (int)$stmt->fetchColumn();
        $this->assertSame(10, $count);
    }
}
