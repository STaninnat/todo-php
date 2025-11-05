<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Tasks\Service;

use App\Api\Request;
use App\Api\Tasks\Service\MarkDoneTaskService;
use App\DB\Database;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use PDO;

final class MarkDoneTaskServiceIntegrationTest extends TestCase
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

        // Clean state
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

        // insert a sample task
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id, is_done)
            VALUES ('Initial task', 'For marking done test', 'user_123', 0)
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

        return new Request('PATCH', '/tasks/1/done', [], $json);
    }

    public function testMarkTaskAsDoneSuccessfully(): void
    {
        $service = new MarkDoneTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => 1,
            'user_id' => 'user_123',
            'is_done' => true,
        ]);

        $result = $service->execute($req);

        assert(isset($result['task']['is_done']) && is_numeric($result['task']['is_done']));
        $this->assertArrayHasKey('task', $result);
        $this->assertSame(1, $result['task']['id']);
        $this->assertSame(1, (int)$result['task']['is_done']);
    }

    public function testMarkTaskAsUndoneSuccessfully(): void
    {
        $this->pdo->exec("UPDATE tasks SET is_done = 1 WHERE id = 1");

        $service = new MarkDoneTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => 1,
            'user_id' => 'user_123',
            'is_done' => false,
        ]);

        $result = $service->execute($req);

        assert(isset($result['task']['is_done']) && is_numeric($result['task']['is_done']));
        $this->assertSame(0, (int)$result['task']['is_done']);
    }

    public function testMarkTaskFailsIfNotFound(): void
    {
        $service = new MarkDoneTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => 999,
            'user_id' => 'user_123',
            'is_done' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No task found.');
        $service->execute($req);
    }

    public function testMarkTaskFailsIfMissingUserId(): void
    {
        $service = new MarkDoneTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => 1,
            'is_done' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required.');
        $service->execute($req);
    }

    public function testMarkTaskFailsIfInvalidIsDoneType(): void
    {
        $service = new MarkDoneTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => 1,
            'user_id' => 'user_123',
            'is_done' => 'not_a_bool',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status value.');
        $service->execute($req);
    }

    public function testMarkTaskFailsOnDatabaseError(): void
    {
        $service = new MarkDoneTaskService($this->queries);

        // corrupt table to force DB failure
        $this->pdo->exec('ALTER TABLE tasks DROP COLUMN is_done');

        $req = $this->makeRequest([
            'id' => 1,
            'user_id' => 'user_123',
            'is_done' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('mark task as done');
        $service->execute($req);
    }
}
