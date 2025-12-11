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

        // Verify output structure
        $this->assertArrayHasKey('task', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayNotHasKey('totalPages', $result);
        $this->assertCount(2, $result['task']); // only 2 tasks for user_123

        $titles = array_column($result['task'], 'title');
        $this->assertContains('Task A', $titles);
        $this->assertContains('Task B', $titles);
        $this->assertNotContains('Task C', $titles);

        foreach ($result['task'] as $task) {
            $this->assertArrayNotHasKey('user_id', $task);
        }
    }

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
        $this->assertArrayNotHasKey('totalPages', $result);
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
     * Test retrieval of tasks with pagination.
     *
     * Ensures:
     * - Limit parameter restricts number of returned tasks
     * - Pagination metadata is correct
     *
     * @return void
     */
    public function testGetTasksWithPagination(): void
    {
        // Preload tasks into the DB
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id) VALUES
            ('Task 1', 'Desc 1', 'user_page'),
            ('Task 2', 'Desc 2', 'user_page'),
            ('Task 3', 'Desc 3', 'user_page'),
            ('Task 4', 'Desc 4', 'user_page'),
            ('Task 5', 'Desc 5', 'user_page');
        ");

        $service = new GetTasksService($this->queries);

        // Request page 1 with limit 2
        $req = $this->makeRequest(['page' => 1, 'limit' => 2], 'user_page');
        $result = $service->execute($req);

        $this->assertCount(2, $result['task']);
        $this->assertEquals(1, $result['pagination']['current_page']);
        $this->assertEquals(2, $result['pagination']['per_page']);
        $this->assertEquals(5, $result['pagination']['total_items']);
        $this->assertEquals(3, $result['pagination']['total_pages']);

        // Request page 3 (last page, should have 1 item)
        $req = $this->makeRequest(['page' => 3, 'limit' => 2], 'user_page');
        $result = $service->execute($req);

        $this->assertCount(1, $result['task']);
        $this->assertEquals(3, $result['pagination']['current_page']);
    }

}
