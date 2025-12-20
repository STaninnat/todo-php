<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\BulkMarkDoneTaskService;
use App\DB\Database;
use App\DB\TaskQueries;
use App\Api\Request;
use PDO;

require_once __DIR__ . '/../../../bootstrap_db.php';

/**
 * Class BulkMarkDoneTaskServiceIntegrationTest
 *
 * Integration tests for BulkMarkDoneTaskService.
 *
 * @package Tests\Integration\Api\Tasks\Service
 */
final class BulkMarkDoneTaskServiceIntegrationTest extends TestCase
{
    /** @var PDO PDO instance for integration testing */
    private PDO $pdo;

    /** @var TaskQueries TaskQueries instance for testing */
    private TaskQueries $queries;

    /** @var BulkMarkDoneTaskService Service instance under test */
    private BulkMarkDoneTaskService $service;

    /**
     * Setup a fresh database schema before each test.
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
        $this->service = new BulkMarkDoneTaskService($this->queries);

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
     * Helper to create a task.
     *
     * @param string $title Task title
     * @param string $userId User ID owner
     *
     * @return int Created task ID
     */
    private function createTask(string $title, string $userId): int
    {
        $result = $this->queries->addTask($title, 'desc', $userId);
        $this->assertTrue($result->success, "Failed to create task");
        $task = $result->data;
        if (is_array($task) && isset($task['id']) && is_numeric($task['id'])) {
            return (int) $task['id'];
        }
        $this->fail("Created task has no ID");
    }

    /**
     * Test successful bulk mark done.
     *
     * @return void
     */
    public function testBulkMarkDoneSuccessfully(): void
    {
        $id1 = $this->createTask('T1', 'u1');
        $id2 = $this->createTask('T2', 'u1');
        // Initially not done (default)

        $req = new Request('POST', '/tasks/bulk-done');
        $req->auth = ['id' => 'u1'];
        $req->body = ['ids' => [$id1, $id2], 'is_done' => true];

        $result = $this->service->execute($req);

        $this->assertEquals(['count' => 2], $result);

        // Verify updates
        $t1 = $this->queries->getTaskByID($id1, 'u1')->data;
        $t2 = $this->queries->getTaskByID($id2, 'u1')->data;

        $this->assertIsArray($t1);
        $this->assertIsArray($t2);
        $this->assertEquals(1, $t1['is_done']);
        $this->assertEquals(1, $t2['is_done']);
    }

    /**
     * Test successful bulk mark undone.
     *
     * @return void
     */
    public function testBulkMarkUndoneSuccessfully(): void
    {
        // Add task and mark it done first
        $id1 = $this->createTask('T1', 'u1');
        $this->queries->markDone($id1, true, 'u1');

        $req = new Request('POST', '/tasks/bulk-done');
        $req->auth = ['id' => 'u1'];
        $req->body = ['ids' => [$id1], 'is_done' => false];

        $result = $this->service->execute($req);

        $this->assertEquals(['count' => 1], $result);

        $t1 = $this->queries->getTaskByID($id1, 'u1')->data;
        $this->assertIsArray($t1);
        $this->assertEquals(0, $t1['is_done']);
    }

    /**
     * Test ignoring other users' tasks.
     *
     * @return void
     */
    public function testBulkMarkIgnoresOtherUserTasks(): void
    {
        $id1 = $this->createTask('My Task', 'u1');
        $idOther = $this->createTask('Other', 'u2');

        $req = new Request('POST', '/tasks/bulk-done');
        $req->auth = ['id' => 'u1'];
        $req->body = ['ids' => [$id1, $idOther], 'is_done' => true];

        $result = $this->service->execute($req);

        $this->assertEquals(['count' => 1], $result);

        // Verify other task untouched
        $tOther = $this->queries->getTaskByID($idOther, 'u2')->data;
        $this->assertIsArray($tOther);
        $this->assertEquals(0, $tOther['is_done']);
    }
}
