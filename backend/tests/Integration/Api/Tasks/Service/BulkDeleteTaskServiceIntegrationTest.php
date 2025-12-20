<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\BulkDeleteTaskService;
use App\DB\Database;
use App\DB\TaskQueries;
use App\Api\Request;
use PDO;

require_once __DIR__ . '/../../../bootstrap_db.php';

/**
 * Class BulkDeleteTaskServiceIntegrationTest
 *
 * Integration tests for BulkDeleteTaskService.
 *
 * @package Tests\Integration\Api\Tasks\Service
 */
final class BulkDeleteTaskServiceIntegrationTest extends TestCase
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
     * @var BulkDeleteTaskService Service instance under test
     */
    private BulkDeleteTaskService $service;

    /**
     * Setup a fresh database schema before each test.
     *
     * Creates a temporary tasks table and initializes dependencies.
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
        $this->service = new BulkDeleteTaskService($this->queries);

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
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    /**
     * Cleanup the database after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS tasks');
        parent::tearDown();
    }

    /**
     * Helper method to create a task in the database.
     *
     * @param string $title Task title
     * @param string $userId User ID owner of the task
     *
     * @return int The ID of the created task
     */
    private function createTask(string $title, string $userId): int
    {
        $result = $this->queries->addTask($title, 'desc', $userId);
        $this->assertTrue($result->success, "Failed to create task: " . json_encode($result->error));
        // Use ID from returned data
        $task = $result->data;
        if (is_array($task) && isset($task['id']) && is_numeric($task['id'])) {
            return (int) $task['id'];
        }
        $this->fail("Created task has no ID");
    }

    /**
     * Test successful bulk deletion of tasks.
     *
     * Ensures:
     * - Tasks belonging to the user are deleted
     * - The service returns the correct count of deleted tasks
     * - Tasks not in the deletion list are unaffected (though in this test we delete what we create mostly)
     *
     * @return void
     */
    public function testBulkDeleteSuccessfully(): void
    {
        $id1 = $this->createTask('T1', 'u1');
        $id2 = $this->createTask('T2', 'u1');
        $id3 = $this->createTask('T3', 'u1');

        $req = new Request('POST', '/tasks/bulk-delete');
        $req->auth = ['id' => 'u1'];
        $req->body = ['ids' => [$id1, $id2]];

        $result = $this->service->execute($req);

        $this->assertEquals(['count' => 2], $result);

        // Verify deletions
        $f1 = $this->queries->getTaskByID($id1, 'u1');
        $f2 = $this->queries->getTaskByID($id2, 'u1');
        $f3 = $this->queries->getTaskByID($id3, 'u1');

        $this->assertNull($f1->data);
        $this->assertNull($f2->data);
        $this->assertNotNull($f3->data);
    }

    /**
     * Test that bulk delete does not delete tasks belonging to other users.
     *
     * Ensures:
     * - Only the requesting user's tasks are deleted even if other IDs are provided
     * - Cross-user deletion is prevented
     *
     * @return void
     */
    public function testBulkDeleteIgnoresOtherUserTasks(): void
    {
        $id1 = $this->createTask('T1', 'u1');
        $idOther = $this->createTask('Other', 'u2');

        $req = new Request('POST', '/tasks/bulk-delete');
        $req->auth = ['id' => 'u1'];
        $req->body = ['ids' => [$id1, $idOther]];

        $result = $this->service->execute($req);

        // Should only delete 1 task (u1's task)
        $this->assertEquals(['count' => 1], $result);

        // Verify other user task still exists
        $fOther = $this->queries->getTaskByID($idOther, 'u2');
        $this->assertNotNull($fOther->data);
    }
}
