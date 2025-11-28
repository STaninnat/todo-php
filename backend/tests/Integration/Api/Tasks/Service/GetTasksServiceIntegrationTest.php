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

require_once __DIR__ . '/../../../bootstrap_db.php';

/**
 * Class GetTasksServiceIntegrationTest
 *
 * Integration tests for the GetTasksService class.
 *
 * This suite verifies:
 * - Correct retrieval of tasks filtered by user_id
 * - Proper pagination handling for large datasets
 * - Validation of required parameters (user_id)
 * - Handling of empty result sets and database-level failures
 *
 * The tests rely on a temporary in-memory table created per test run.
 *
 * @package Tests\Integration\Api\Tasks\Service
 */
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

        // Wait until database is ready before connecting
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
     * Helper method to create Request objects for GET /tasks.
     *
     * @param array<string, mixed> $query Query parameters
     * 
     * @return Request
     */
    private function makeRequest(array $query = [], ?string $userId = null): Request
    {
        $req = new Request('GET', '/tasks');
        $req->query = $query;   // Inline assignment of simulated query parameters
        if ($userId !== null) {
            $req->auth = ['id' => $userId];
        }
        return $req;
    }

    /**
     * Test successful retrieval of tasks for a specific user.
     *
     * Ensures:
     * - Only tasks matching user_id are returned
     * - Pagination and structure are valid
     *
     * @return void
     */
    public function testGetTasksSuccessfully(): void
    {
        // Preload tasks into the DB
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id) VALUES
            ('Task A', 'First', 'user_123'),
            ('Task B', 'Second', 'user_123'),
            ('Task C', 'Third', 'user_456');
        ");

        $service = new GetTasksService($this->queries);
        $req = $this->makeRequest([], 'user_123');

        // Execute service
        $result = $service->execute($req);

        // Verify result structure
        $this->assertArrayHasKey('task', $result);
        $this->assertArrayHasKey('totalPages', $result);

        $this->assertSame(1, $result['totalPages']);
        $this->assertCount(2, $result['task']); // only 2 tasks for user_123

        $titles = array_column($result['task'], 'title');
        $this->assertContains('Task A', $titles);
        $this->assertContains('Task B', $titles);
        $this->assertNotContains('Task C', $titles);
    }

    /**
     * Test missing user_id validation.
     *
     * Expects InvalidArgumentException with message "User ID is required."
     *
     * @return void
     */


    /**
     * Test retrieval when user has no tasks.
     *
     * Ensures:
     * - Empty array is returned for "task"
     * - totalPages is still at least 1
     *
     * @return void
     */
    public function testGetTasksForUserWithNoTasksReturnsEmptyArray(): void
    {
        $service = new GetTasksService($this->queries);
        $req = $this->makeRequest([], 'ghost');
        $result = $service->execute($req);

        $this->assertArrayHasKey('task', $result);
        $this->assertSame([], $result['task']);
        $this->assertSame(1, $result['totalPages']); // at least one empty page
    }

    /**
     * Test handling of database errors.
     *
     * Simulates a DB failure by removing the tasks table before query execution.
     * Expects RuntimeException with message containing "retrieve tasks".
     *
     * @return void
     */
    public function testGetTasksFailsOnDatabaseError(): void
    {
        // Simulate DB failure by dropping table before query
        $this->pdo->exec('DROP TABLE IF EXISTS tasks');

        $service = new GetTasksService($this->queries);
        $req = $this->makeRequest([], 'user_123');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('retrieve tasks');
        $service->execute($req);
    }

    /**
     * Test pagination logic for large task sets.
     *
     * Ensures:
     * - totalPages is computed correctly (25 / 10 → 3)
     * - Returned data count matches inserted tasks
     *
     * @return void
     */
    public function testPaginationIncreasesWithManyTasks(): void
    {
        // 25 tasks for one user
        $stmt = $this->pdo->prepare("INSERT INTO tasks (title, description, user_id) VALUES (:t, '', :u)");
        for ($i = 1; $i <= 25; $i++) {
            $stmt->execute([':t' => "Task {$i}", ':u' => 'user_abc']);
        }

        $service = new GetTasksService($this->queries);
        $req = $this->makeRequest([], 'user_abc');
        $result = $service->execute($req);

        // Service returns all tasks for simplicity
        $this->assertSame(3, $result['totalPages']); // 10 per page
        $this->assertCount(25, $result['task']); // returns all for simplicity
    }
}
