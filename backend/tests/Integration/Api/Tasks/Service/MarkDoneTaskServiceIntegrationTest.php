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

require_once __DIR__ . '/../../../bootstrap_db.php';

/**
 * Class MarkDoneTaskServiceIntegrationTest
 *
 * Integration tests for the MarkDoneTaskService class.
 *
 * This test suite verifies:
 * - Correct toggling of task completion status (done/undone)
 * - Proper validation of required fields and types
 * - Handling of missing or invalid inputs
 * - Database error handling and integrity validation
 *
 * Each test uses an isolated database table recreated per run.
 *
 * @package Tests\Integration\Api\Tasks\Service
 */
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

    /**
     * Setup database connection and seed initial data.
     *
     * Creates a fresh tasks table with a single record for update testing.
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

        // Clean state
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

        // insert a sample task
        $this->pdo->exec("
            INSERT INTO tasks (title, description, user_id, is_done)
            VALUES ('Initial task', 'For marking done test', 'user_123', 0)
        ");
    }

    /**
     * Clean up database after each test case.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS tasks');
        parent::tearDown();
    }

    /**
     * Helper to create mock Request objects for PATCH /tasks/{id}/done endpoint.
     *
     * @param array<string, mixed> $body Request body payload
     * 
     * @return Request
     */
    private function makeRequest(array $body): Request
    {
        $json = json_encode($body);
        if ($json === false) {
            throw new RuntimeException('Failed to encode request body to JSON.');
        }

        return new Request('PATCH', '/tasks/1/done', [], $json);
    }

    /**
     * Test successful marking of a task as done.
     *
     * Ensures:
     * - Task record is updated in DB
     * - Response includes updated task data
     * - `is_done` field reflects correct boolean conversion
     *
     * @return void
     */
    public function testMarkTaskAsDoneSuccessfully(): void
    {
        $service = new MarkDoneTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => 1,
            'user_id' => 'user_123',
            'is_done' => true,
        ]);

        $result = $service->execute($req);

        // Validate structure and correctness of returned data
        assert(isset($result['task']['is_done']) && is_numeric($result['task']['is_done']));
        $this->assertArrayHasKey('task', $result);
        $this->assertSame(1, $result['task']['id']);
        $this->assertSame(1, (int) $result['task']['is_done']);
    }

    /**
     * Test successful marking of a task as undone.
     *
     * Ensures:
     * - A previously completed task can be reverted to undone state
     * - Updated record correctly reflects `is_done = 0`
     *
     * @return void
     */
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

        // Inline note: `is_done` must now be 0 (false)
        assert(isset($result['task']['is_done']) && is_numeric($result['task']['is_done']));
        $this->assertSame(0, (int) $result['task']['is_done']);
    }

    /**
     * Test service behavior when attempting to update a non-existent task.
     *
     * Expects RuntimeException with message "No task found."
     *
     * @return void
     */
    public function testMarkTaskFailsIfNotFound(): void
    {
        $service = new MarkDoneTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => 999,
            'user_id' => 'user_123',
            'is_done' => true,
        ]);

        // Expect failure due to non-existent ID
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No task found.');
        $service->execute($req);
    }

    /**
     * Test validation failure when user_id is missing.
     *
     * Expects InvalidArgumentException with message "User ID is required."
     *
     * @return void
     */
    public function testMarkTaskFailsIfMissingUserId(): void
    {
        $service = new MarkDoneTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => 1,
            'is_done' => true,
        ]);

        // Expect validation failure due to missing user_id
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required.');
        $service->execute($req);
    }

    /**
     * Test validation failure for invalid is_done type.
     *
     * Ensures that non-boolean values are rejected properly.
     *
     * @return void
     */
    public function testMarkTaskFailsIfInvalidIsDoneType(): void
    {
        $service = new MarkDoneTaskService($this->queries);

        $req = $this->makeRequest([
            'id' => 1,
            'user_id' => 'user_123',
            'is_done' => 'not_a_bool',
        ]);

        // Expect validation failure due to invalid type
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status value.');
        $service->execute($req);
    }

    /**
     * Test handling of database-level errors during update.
     *
     * Simulates a DB failure by removing the `is_done` column.
     * Expects RuntimeException containing phrase "mark task as done".
     *
     * @return void
     */
    public function testMarkTaskFailsOnDatabaseError(): void
    {
        $service = new MarkDoneTaskService($this->queries);

        // Force schema corruption to trigger DB error
        $this->pdo->exec('ALTER TABLE tasks DROP COLUMN is_done');

        $req = $this->makeRequest([
            'id' => 1,
            'user_id' => 'user_123',
            'is_done' => true,
        ]);

        // Expect DB failure handled by service layer
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('mark task as done');
        $service->execute($req);
    }
}
