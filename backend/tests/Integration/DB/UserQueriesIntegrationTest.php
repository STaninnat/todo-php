<?php

declare(strict_types=1);

namespace Tests\Integration\DB;

use App\DB\Database;
use App\DB\UserQueries;
use PHPUnit\Framework\TestCase;
use PDO;

require_once __DIR__ . '/../bootstrap_db.php';

/**
 * Class UserQueriesIntegrationTest
 *
 * Integration tests for the UserQueries class.
 *
 * This test suite verifies:
 * - CRUD operations on the users table
 * - Validation for duplicate users and long input values
 * - User existence checks
 *
 * Uses a real database connection and requires bootstrap_db.php.
 *
 * @package Tests\Integration\DB
 */
final class UserQueriesIntegrationTest extends TestCase
{
    /**
     * @var PDO PDO instance for integration testing
     */
    private PDO $pdo;

    /**
     * @var UserQueries UserQueries instance for testing
     */
    private UserQueries $queries;

    /**
     * @var string Example user IDs
     */
    private string $userId1 = 'user_1';
    private string $userId2 = 'user_2';

    /**
     * Setup before each test.
     *
     * Establishes a real database connection, creates the users table,
     * and initializes UserQueries instance.
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
        $this->queries = new UserQueries($this->pdo);

        // Recreate users table for clean test state
        $this->pdo->exec('DROP TABLE IF EXISTS users');
        $this->pdo->exec("
            CREATE TABLE users (
                id VARCHAR(64) PRIMARY KEY,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    /**
     * Test creating a user and fetching it by ID.
     *
     * @return void
     */
    public function testCreateUserAndGetByID(): void
    {
        $result = $this->queries->createUser($this->userId1, 'alice', 'alice@example.com', 'password123');
        $this->assertTrue($result->success);

        assert(is_array($result->data));
        $this->assertSame($this->userId1, $result->data['id']);
        $this->assertSame('alice', $result->data['username']);

        // Fetch user and verify data
        $fetch = $this->queries->getUserByID($this->userId1);

        $this->assertTrue($fetch->success);
        assert(is_array($fetch->data));
        $this->assertSame('alice', $fetch->data['username']);
        $this->assertSame('alice@example.com', $fetch->data['email']);
    }

    /**
     * Test fetching a user by username.
     *
     * @return void
     */
    public function testGetUserByName(): void
    {
        $this->queries->createUser($this->userId2, 'bob', 'bob@example.com', 'pass456');

        $fetch = $this->queries->getUserByName('bob');
        $this->assertTrue($fetch->success);

        assert(is_array($fetch->data));
        $this->assertSame($this->userId2, $fetch->data['id']);
        $this->assertSame('bob@example.com', $fetch->data['email']);
    }

    /**
     * Test checking if a user exists by username/email.
     *
     * @return void
     */
    public function testCheckUserExists(): void
    {
        $this->queries->createUser($this->userId1, 'alice', 'alice@example.com', 'password123');

        $exists1 = $this->queries->checkUserExists('alice', 'nonexistent@example.com');

        $this->assertTrue($exists1->success);
        $this->assertTrue($exists1->data);  // user exists

        $exists2 = $this->queries->checkUserExists('charlie', 'charlie@example.com');
        $this->assertTrue($exists2->success);
        $this->assertFalse($exists2->data); // user does not exist
    }

    /**
     * Test updating user fields.
     *
     * @return void
     */
    public function testUpdateUser(): void
    {
        $this->queries->createUser($this->userId1, 'alice', 'alice@example.com', 'password123');

        $update = $this->queries->updateUser($this->userId1, 'alice_new', 'alice_new@example.com');

        $this->assertTrue($update->success);
        assert(is_array($update->data));
        $this->assertSame('alice_new', $update->data['username']);
        $this->assertSame('alice_new@example.com', $update->data['email']);
    }

    /**
     * Test deleting a user.
     *
     * @return void
     */
    public function testDeleteUser(): void
    {
        $this->queries->createUser($this->userId2, 'bob', 'bob@example.com', 'pass456');

        $delete = $this->queries->deleteUser($this->userId2);
        $this->assertTrue($delete->success);
        $this->assertSame(1, $delete->affected);

        // Verify deletion
        $fetch = $this->queries->getUserByID($this->userId2);

        $this->assertTrue($fetch->success);
        $this->assertNull($fetch->data);
    }

    /**
     * Test creating a user with a duplicate ID fails.
     *
     * @return void
     */
    public function testCreateUserFailureDuplicateID(): void
    {
        $this->queries->createUser($this->userId1, 'alice', 'alice@example.com', 'password123');

        $result = $this->queries->createUser($this->userId1, 'alice2', 'alice2@example.com', 'pass2');

        $this->assertFalse($result->success);
        $this->assertNotEmpty($result->error);
    }

    /**
     * Test creating multiple users and retrieving them.
     *
     * @return void
     */
    public function testMultipleUsersCreateAndRetrieve(): void
    {
        $users = [
            ['id' => 'u1', 'username' => 'user1', 'email' => 'u1@example.com'],
            ['id' => 'u2', 'username' => 'user2', 'email' => 'u2@example.com'],
            ['id' => 'u3', 'username' => 'user3', 'email' => 'u3@example.com'],
        ];

        // Create multiple users
        foreach ($users as $u) {
            $this->queries->createUser($u['id'], $u['username'], $u['email'], 'pass123');
        }

        // Fetch and validate each user
        foreach ($users as $u) {
            $res = $this->queries->getUserByID($u['id']);

            $this->assertTrue($res->success);
            assert(is_array($res->data));
            $this->assertSame($u['username'], $res->data['username']);
            $this->assertSame($u['email'], $res->data['email']);
        }
    }

    /**
     * Test checkUserExists with multiple users.
     *
     * @return void
     */
    public function testCheckUserExistsWithMultipleUsers(): void
    {
        $this->queries->createUser('userX', 'alice', 'alice@example.com', 'pass');
        $this->queries->createUser('userY', 'bob', 'bob@example.com', 'pass');

        $res1 = $this->queries->checkUserExists('alice', 'bob@example.com');
        $this->assertTrue($res1->success);
        $this->assertTrue($res1->data);     // some user exists

        $res2 = $this->queries->checkUserExists('charlie', 'dave@example.com');
        $this->assertTrue($res2->success);
        $this->assertFalse($res2->data);    // no user exists
    }

    /**
     * Test updating a non-existent user returns null data.
     *
     * @return void
     */
    public function testUpdateNonExistentUser(): void
    {
        $res = $this->queries->updateUser('non_exist', 'newname', 'new@example.com');

        $this->assertTrue($res->success);
        $this->assertNull($res->data);
    }

    /**
     * Test deleting a non-existent user returns 0 affected rows.
     *
     * @return void
     */
    public function testDeleteNonExistentUser(): void
    {
        $res = $this->queries->deleteUser('non_exist');

        $this->assertTrue($res->success);
        $this->assertSame(0, $res->affected);
    }

    /**
     * Test creating a user with excessively long username/email fails.
     *
     * @return void
     */
    public function testCreateUserWithTooLongValues(): void
    {
        $longUsername = str_repeat('a', 300);
        $longEmail = str_repeat('b', 300) . '@example.com';

        $res = $this->queries->createUser('u_long', $longUsername, $longEmail, 'pass');

        $this->assertFalse($res->success);
        $this->assertNotEmpty($res->error); // validation error expected
    }

    /**
     * Cleanup after each test.
     *
     * Drops the users table to avoid side effects.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS users');
        parent::tearDown();
    }
}
