<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Tasks\Service;

use App\Api\Request;
use App\Api\Tasks\Service\GetTasksService;
use App\DB\Database;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use PDO;

final class GetTasksServiceIntegrationTest extends TestCase
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
        $dbPort = (int) $dbPort;

        waitForDatabase($dbHost, $dbPort);

        $this->pdo = (new Database())->getConnection();
        $this->queries = new TaskQueries($this->pdo);

        // Fresh table
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
     * @param array<string, mixed> $query
     */
    private function makeRequest(array $query = []): Request
    {
        $req = new Request('GET', '/tasks');
        $req->query = $query;
        return $req;
    }

    public function testGetTasksSuccessfully(): void
    {
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id) VALUES
            ('Task A', 'First', 'user_123'),
            ('Task B', 'Second', 'user_123'),
            ('Task C', 'Third', 'user_456');
        ");

        $service = new GetTasksService($this->queries);
        $req = $this->makeRequest(['user_id' => 'user_123']);
        $result = $service->execute($req);

        $this->assertArrayHasKey('task', $result);
        $this->assertArrayHasKey('totalPages', $result);

        $this->assertSame(1, $result['totalPages']);
        $this->assertCount(2, $result['task']); // only 2 tasks for user_123

        $titles = array_column($result['task'], 'title');
        $this->assertContains('Task A', $titles);
        $this->assertContains('Task B', $titles);
        $this->assertNotContains('Task C', $titles);
    }

    public function testGetTasksWithoutUserIdThrowsException(): void
    {
        $service = new GetTasksService($this->queries);
        $req = $this->makeRequest();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required.');
        $service->execute($req);
    }

    public function testGetTasksForUserWithNoTasksReturnsEmptyArray(): void
    {
        $service = new GetTasksService($this->queries);
        $req = $this->makeRequest(['user_id' => 'ghost']);
        $result = $service->execute($req);

        $this->assertArrayHasKey('task', $result);
        $this->assertSame([], $result['task']);
        $this->assertSame(1, $result['totalPages']); // at least one empty page
    }

    public function testGetTasksFailsOnDatabaseError(): void
    {
        // Simulate DB failure by dropping table before query
        $this->pdo->exec('DROP TABLE IF EXISTS tasks');

        $service = new GetTasksService($this->queries);
        $req = $this->makeRequest(['user_id' => 'user_123']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('retrieve tasks');
        $service->execute($req);
    }

    public function testPaginationIncreasesWithManyTasks(): void
    {
        // 25 tasks for one user
        $stmt = $this->pdo->prepare("INSERT INTO tasks (title, description, user_id) VALUES (:t, '', :u)");
        for ($i = 1; $i <= 25; $i++) {
            $stmt->execute([':t' => "Task {$i}", ':u' => 'user_abc']);
        }

        $service = new GetTasksService($this->queries);
        $req = $this->makeRequest(['user_id' => 'user_abc']);
        $result = $service->execute($req);

        $this->assertSame(3, $result['totalPages']); // 10 per page
        $this->assertCount(25, $result['task']); // returns all for simplicity
    }
}
