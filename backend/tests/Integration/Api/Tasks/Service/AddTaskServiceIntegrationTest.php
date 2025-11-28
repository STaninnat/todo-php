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

require_once __DIR__ . '/../../../bootstrap_db.php';

/**
 * Class AddTaskServiceIntegrationTest
 *
 * Integration tests for the AddTaskService class.
 *
 * This test suite verifies:
 * - Proper validation of required fields (title, user_id)
 * - Correct insertion into database and returned structure
 * - Exception handling for invalid or missing inputs
 * - Pagination logic correctness upon multiple task insertions
 *
 * Relies on a temporary tasks table created during setup.
 *
 * @package Tests\Integration\Api\Tasks\Service
 */
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

    /**
     * Setup a fresh database and recreate the tasks table before each test.
     *
     * Loads test database config, waits for connection readiness,
     * and initializes required schema.
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

        // Wait until test DB is available before proceeding
        waitForDatabase($dbHost, $dbPort);

        $this->pdo = (new Database())->getConnection();
        $this->queries = new TaskQueries($this->pdo);

        // Recreate tasks table to ensure isolation between tests
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
     * Cleanup database after each test case.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS tasks');
        parent::tearDown();
    }

    /**
     * Helper to build Request objects with JSON body.
     *
     * @param array<string, mixed> $body Request body
     * 
     * @return Request
     */
    private function makeRequest(array $body, ?string $userId = null): Request
    {
        $json = json_encode($body);
        if ($json === false) {
            throw new RuntimeException('Failed to encode request body to JSON.');
        }

        // Return simulated API Request
        $req = new Request('POST', '/tasks', [], $json);
        if ($userId !== null) {
            $req->auth = ['id' => $userId];
        }
        return $req;
    }

    /**
     * Test successful addition of a new task.
     *
     * Ensures:
     * - Task is inserted into DB
     * - Returned response includes 'task' and pagination data
     * - Stored task matches the input values
     *
     * @return void
     */
    public function testAddTaskSuccessfully(): void
    {
        $service = new AddTaskService($this->queries);

        // Build request payload
        $req = $this->makeRequest([
            'title' => 'Integration Task',
            'description' => 'This is a sample task for integration test.',
        ], 'user_123');

        // Execute service
        $result = $service->execute($req);

        // Verify output structure
        $this->assertArrayHasKey('task', $result);
        $this->assertArrayHasKey('totalPages', $result);
        $this->assertSame(1, $result['totalPages']);

        // Validate inserted data consistency
        $task = $result['task'];
        $this->assertSame('Integration Task', $task['title']);
        $this->assertSame('This is a sample task for integration test.', $task['description']);
        $this->assertSame('user_123', $task['user_id']);

        // Check actual DB persistence
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM tasks');
        if ($stmt === false) {
            throw new RuntimeException('Failed to count tasks.');
        }

        $count = (int) $stmt->fetchColumn();
        $this->assertSame(1, $count);
    }

    /**
     * Test missing title validation.
     *
     * Expects InvalidArgumentException with message "Task title is required."
     *
     * @return void
     */
    public function testAddTaskWithoutTitleThrowsException(): void
    {
        $service = new AddTaskService($this->queries);

        $req = $this->makeRequest([
            'description' => 'Missing title field',
        ], 'user_123');

        // Expect validation failure due to missing title
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task title is required.');
        $service->execute($req);
    }



    /**
     * Test validation of incorrect input types.
     *
     * Ensures the service rejects non-string types for required fields.
     *
     * @return void
     */
    public function testAddTaskWithInvalidTypesThrowsException(): void
    {
        $service = new AddTaskService($this->queries);

        // invalid type: title=array
        $req = $this->makeRequest([
            'title' => ['wrong'],
        ], '123');

        // Expect validation failure
        $this->expectException(InvalidArgumentException::class);
        $service->execute($req);
    }

    /**
     * Test database-level insertion failure.
     *
     * Simulates DB error by providing title exceeding column length (255 chars).
     *
     * Expects RuntimeException containing the phrase "add task".
     *
     * @return void
     */
    public function testAddTaskFailsOnDatabaseError(): void
    {
        $service = new AddTaskService($this->queries);

        // create title longer than 255 chars -> DB insert should fail
        $longTitle = str_repeat('x', 300);
        $req = $this->makeRequest([
            'title' => $longTitle,
        ], 'user_999');

        // Expect DB failure wrapped in RuntimeException
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('add task');
        $service->execute($req);
    }

    /**
     * Test pagination logic after inserting multiple tasks.
     *
     * Ensures that totalPages increases correctly based on inserted count.
     * (25 tasks / perPage=10 → totalPages = 3)
     *
     * @return void
     */
    public function testPaginationIncreasesWithMultipleTasks(): void
    {
        $service = new AddTaskService($this->queries);

        // Insert 25 tasks (perPage = 10 => expect totalPages = 3)
        for ($i = 1; $i <= 25; $i++) {
            $req = $this->makeRequest([
                'title' => "Task {$i}",
            ], 'user_abc');

            // Inline note: the last iteration's result will reflect the final totalPages
            $result = $service->execute($req);
        }

        // Verify computed pagination
        $this->assertSame(3, $result['totalPages']);
    }
}
