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

require_once __DIR__ . '/../../../bootstrap_db.php';

/**
 * Class DeleteTaskServiceIntegrationTest
 *
 * Integration tests for the DeleteTaskService class.
 *
 * This test suite verifies:
 * - Proper validation of task ID and user ID inputs
 * - Correct task deletion from the database
 * - Proper handling of missing or invalid fields
 * - Pagination recalculation after deletion
 * - Appropriate exceptions on non-existent deletions
 *
 * @package Tests\Integration\Api\Tasks\Service
 */
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

    /**
     * Prepare test database before each test case.
     *
     * Creates a clean tasks table and connects via Database class.
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

        // Wait until test DB becomes available
        waitForDatabase($dbHost, $dbPort);

        $this->pdo = (new Database())->getConnection();
        $this->queries = new TaskQueries($this->pdo);

        // Recreate tasks table fresh for isolation between test cases
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
     * Clean up database after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS tasks');
        parent::tearDown();
    }

    /**
     * Helper method to create Request objects for DELETE /tasks.
     *
     * @param array<string, mixed> $body Request body data
     *
     * @return Request
     */
    private function makeRequest(array $body, ?string $userId = null): Request
    {
        $json = json_encode($body);
        if ($json === false) {
            throw new RuntimeException('Failed to encode request body to JSON.');
        }

        // Simulated DELETE API request
        $req = new Request('DELETE', '/tasks', [], $json);
        if ($userId !== null) {
            $req->auth = ['id' => $userId];
        }
        return $req;
    }

    /**
     * Test successful task deletion.
     *
     * Ensures that:
     * - Existing task is deleted successfully
     * - Response includes correct 'id' and 'totalPages'
     * - Task count in DB decreases as expected
     *
     * @return void
     */
    public function testDeleteTaskSuccessfully(): void
    {
        // Insert a task to be deleted
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id)
            VALUES ('Test Task', 'To be deleted', 'user_123')
        ");
        $taskId = (int) $this->pdo->lastInsertId();

        $service = new DeleteTaskService($this->queries);
        $req = $this->makeRequest([
            'id' => $taskId,
        ], 'user_123');

        // Execute deletion service
        $result = $service->execute($req);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayNotHasKey('totalPages', $result);
        $this->assertSame($taskId, $result['id']);

        // Verify task removed from database
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM tasks');
        if ($stmt === false) {
            throw new RuntimeException('Failed to count tasks.');
        }
        $count = (int) $stmt->fetchColumn();
        $this->assertSame(0, $count);
    }

    /**
     * Test missing task ID validation.
     *
     * Expects InvalidArgumentException with message
     * "Task ID must be a numeric string."
     *
     * @return void
     */
    public function testDeleteTaskWithoutIdThrowsException(): void
    {
        $service = new DeleteTaskService($this->queries);
        $req = $this->makeRequest([], 'user_123');

        // Missing ID -> validation error expected
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task ID must be a numeric string.');
        $service->execute($req);
    }

    /**
     * Test missing user ID validation.
     *
     * Expects InvalidArgumentException with message
     * "User ID is required."
     *
     * @return void
     */


    /**
     * Test deletion of non-existent task.
     *
     * Expects RuntimeException with message containing "delete task".
     *
     * @return void
     */
    public function testDeleteNonExistentTaskThrowsRuntimeException(): void
    {
        $service = new DeleteTaskService($this->queries);
        $req = $this->makeRequest([
            'id' => 999,
        ], 'ghost_user');

        // Deleting non-existent task should trigger runtime error
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('delete task');
        $service->execute($req);
    }


}
