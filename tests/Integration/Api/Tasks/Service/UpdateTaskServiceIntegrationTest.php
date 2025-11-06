<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Tasks\Service;

use App\Api\Request;
use App\Api\Tasks\Service\UpdateTaskService;
use App\DB\Database;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use PDO;

/**
 * Class UpdateTaskServiceIntegrationTest
 *
 * Integration tests for the UpdateTaskService class.
 *
 * This suite verifies:
 * - Proper validation of required fields (title, user_id, id)
 * - Successful update of existing tasks
 * - Correct handling of missing or invalid inputs
 * - Graceful error responses for non-existent or invalid records
 *
 * Uses a temporary `tasks` table created and reset per test run.
 *
 * @package Tests\Integration\Api\Tasks\Service
 */
final class UpdateTaskServiceIntegrationTest extends TestCase
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
     * Setup a clean database and table before each test.
     *
     * Loads the test DB environment, ensures the connection is available,
     * and initializes a fresh `tasks` table.
     *
     * @return void
     */
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

    /**
     * Tear down the test environment.
     *
     * Drops the temporary table and calls parent teardown.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS tasks');
        parent::tearDown();
    }

    /**
     * Create a sample task record for update testing.
     *
     * @return array<string, int|string> Inserted task data
     */
    private function createSampleTask(): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO tasks (title, description, user_id) VALUES (?, ?, ?)');
        $stmt->execute(['Old Title', 'Old description', 'user_123']);
        $id = (int)$this->pdo->lastInsertId();

        return [
            'id' => $id,
            'title' => 'Old Title',
            'description' => 'Old description',
            'user_id' => 'user_123',
        ];
    }

    /**
     * Helper to construct a Request object for testing.
     *
     * @param array<string, mixed> $body Request body content
     * 
     * @return Request
     */
    private function makeRequest(array $body): Request
    {
        $json = json_encode($body);
        if ($json === false) {
            throw new RuntimeException('Failed to encode request body to JSON.');
        }

        return new Request('PUT', '/tasks/update', [], $json);
    }

    /**
     * Test successful task update with valid input.
     *
     * Verifies that:
     * - Task fields are properly updated in the database
     * - Response structure contains expected values
     *
     * @return void
     */
    public function testUpdateTaskSuccessfully(): void
    {
        $task = $this->createSampleTask();
        $service = new UpdateTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => $task['id'],
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'is_done' => true,
            'user_id' => $task['user_id'],
        ]);

        // Execute service
        $result = $service->execute($req);

        // Inline: Ensure the returned data contains numeric 'is_done'
        assert(isset($result['task']['is_done']) && is_numeric($result['task']['is_done']));

        // Validate response structure and updated data
        $this->assertArrayHasKey('task', $result);
        $this->assertSame('Updated Title', $result['task']['title']);
        $this->assertSame('Updated description', $result['task']['description']);
        $this->assertSame(1, (int)$result['task']['is_done']);
        $this->assertSame(1, $result['totalPages']);
    }

    /**
     * Test missing title validation.
     *
     * Expects InvalidArgumentException with "Task title is required."
     *
     * @return void
     */
    public function testUpdateTaskWithoutTitleThrowsException(): void
    {
        $task = $this->createSampleTask();
        $service = new UpdateTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => $task['id'],
            'is_done' => true,
            'user_id' => $task['user_id'],
        ]);

        // Expect validation failure due to missing title
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task title is required.');
        $service->execute($req);
    }

    /**
     * Test missing user_id validation.
     *
     * Expects InvalidArgumentException with "User ID is required."
     *
     * @return void
     */
    public function testUpdateTaskWithoutUserIdThrowsException(): void
    {
        $task = $this->createSampleTask();
        $service = new UpdateTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => $task['id'],
            'title' => 'No User',
            'is_done' => false,
        ]);

        // Expect validation failure due to missing user_id
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required.');
        $service->execute($req);
    }

    /**
     * Test validation of incorrect input types.
     *
     * Ensures invalid data types trigger InvalidArgumentException.
     *
     * @return void
     */
    public function testUpdateTaskWithInvalidTypesThrowsException(): void
    {
        $task = $this->createSampleTask();
        $service = new UpdateTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => 'invalid',
            'title' => ['array'],
            'is_done' => 'wrong',
            'user_id' => $task['user_id'],
        ]);

        // Expect validation failure
        $this->expectException(InvalidArgumentException::class);
        $service->execute($req);
    }

    /**
     * Test behavior when attempting to update a non-existent task.
     *
     * Expects RuntimeException with message "No task found."
     *
     * @return void
     */
    public function testUpdateTaskFailsWhenTaskNotFound(): void
    {
        $service = new UpdateTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => 9999,
            'title' => 'Nonexistent',
            'description' => 'Should fail',
            'is_done' => false,
            'user_id' => 'user_123',
        ]);

        // Expect runtime exception due to missing record
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No task found.');
        $service->execute($req);
    }

    /**
     * Test handling of database errors (e.g. constraint violations).
     *
     * Simulates SQL error by exceeding VARCHAR(255) column limit.
     *
     * Expects RuntimeException with message containing "update task".
     *
     * @return void
     */
    public function testUpdateTaskFailsOnDatabaseError(): void
    {
        $task = $this->createSampleTask();
        $service = new UpdateTaskService($this->queries);

        // Overly long title to force SQL error
        $longTitle = str_repeat('x', 300);
        $req = $this->makeRequest([
            'id' => $task['id'],
            'title' => $longTitle,
            'description' => 'bad data',
            'is_done' => true,
            'user_id' => $task['user_id'],
        ]);

        // Expect RuntimeException on failed update
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('update task');
        $service->execute($req);
    }
}
