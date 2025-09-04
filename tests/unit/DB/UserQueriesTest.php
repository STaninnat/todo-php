<?php

use PHPUnit\Framework\TestCase;
use App\DB\UserQueries;

/**
 * Unit tests for the UserQueries class.
 *
 * This test suite verifies CRUD and user management operations:
 * - createUser(), getUserByName(), getUserByID()
 * - checkUserExists(), updateUser(), deleteUser()
 *
 * Uses PDOStatement and PDO mocks to avoid real database connections.
 */
class UserQueriesTest extends TestCase
{
    private $pdo;
    private $stmt;
    private UserQueries $userQueries;

    /**
     * Setup mocks for PDO and PDOStatement before each test.
     *
     * - Mock execute, fetch, fetchAll, rowCount, fetchColumn
     * - Inject mocked PDO into UserQueries instance
     */
    protected function setUp(): void
    {
        // Create a mock PDOStatement
        $this->stmt = $this->createMock(PDOStatement::class);

        // Create a mock PDO
        $this->pdo = $this->createMock(PDO::class);

        // Make PDO mock return the PDOStatement mock when prepare() is called
        $this->pdo->method('prepare')->willReturn($this->stmt);

        // Instantiate UserQueries with mocked PDO
        $this->userQueries = new UserQueries($this->pdo);
    }

    /** ----------------- createUser ----------------- */
    /**
     * Test: createUser should return success with proper user data when execute succeeds.
     */
    public function testCreateUserSuccess()
    {
        $user = ['id' => '1', 'username' => 'test', 'email' => 't@test.com', 'password' => 'pass'];
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn($user);

        $result = $this->userQueries->createUser('1', 'test', 't@test.com', 'pass');

        $this->assertTrue($result->success);
        $this->assertEquals($user, $result->data);
    }

    /**
     * Test: createUser should return failure with error info when execute fails.
     */
    public function testCreateUserFail()
    {
        $error = ['code' => '123', 'message' => 'DB error'];
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn($error);

        $result = $this->userQueries->createUser('1', 'fail', 'f@test.com', 'pass');

        $this->assertFalse($result->success);
        $this->assertEquals($error, $result->error);
    }

    /**
     * Test: createUser returns ok but getUserByID fetch returns null (no user found).
     */
    public function testCreateUserButGetUserByIdReturnsNull()
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn(false);

        $result = $this->userQueries->createUser('2', 'lost', 'lost@test.com', 'secret');

        $this->assertTrue($result->success);
        $this->assertNull($result->data);
        $this->assertEquals(0, $result->affected);
    }

    /** ----------------- getUserByName ----------------- */
    /**
     * Test: getUserByName should return user when found.
     */
    public function testGetUserByNameFound()
    {
        $user = ['id' => '1', 'username' => 'test', 'email' => 't@test.com'];
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn($user);

        $result = $this->userQueries->getUserByName('test');

        $this->assertTrue($result->success);
        $this->assertEquals($user, $result->data);
        $this->assertEquals(1, $result->affected);
    }

    /**
     * Test: getUserByName should return null if not found.
     */
    public function testGetUserByNameNotFound()
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn(false);

        $result = $this->userQueries->getUserByName('none');

        $this->assertTrue($result->success);
        $this->assertNull($result->data);
        $this->assertEquals(0, $result->affected);
    }

    /**
     * Test: getUserByName should return failure if execute fails.
     */
    public function testGetUserByNameFail()
    {
        $error = ['code' => 'err', 'message' => 'DB fail'];
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn($error);

        $result = $this->userQueries->getUserByName('bad');

        $this->assertFalse($result->success);
        $this->assertEquals($error, $result->error);
    }

    /** ----------------- getUserByID ----------------- */
    /**
     * Test: getUserByID should return user when found.
     */
    public function testGetUserByIDFound()
    {
        $user = ['id' => '1', 'username' => 'test', 'email' => 't@test.com'];
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn($user);

        $result = $this->userQueries->getUserByID('1');

        $this->assertTrue($result->success);
        $this->assertEquals($user, $result->data);
        $this->assertEquals(1, $result->affected);
    }

    /**
     * Test: getUserByID should return null if not found.
     */
    public function testGetUserByIDNotFound()
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn(false);

        $result = $this->userQueries->getUserByID('999');

        $this->assertTrue($result->success);
        $this->assertNull($result->data);
        $this->assertEquals(0, $result->affected);
    }

    /**
     * Test: getUserByID should return failure if execute fails.
     */
    public function testGetUserByIDFail()
    {
        $error = ['code' => 'err', 'message' => 'DB fail'];
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn($error);

        $result = $this->userQueries->getUserByID('failid');

        $this->assertFalse($result->success);
        $this->assertEquals($error, $result->error);
    }

    /** ----------------- checkUserExists ----------------- */
    /**
     * Test: checkUserExists should return true if a user exists.
     */
    public function testCheckUserExistsTrue()
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchColumn')->willReturn(1);

        $result = $this->userQueries->checkUserExists('test', 't@test.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data);
        $this->assertEquals(1, $result->affected);
    }

    /**
     * Test: checkUserExists should return false if user not found.
     */
    public function testCheckUserExistsFalse()
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchColumn')->willReturn(0);

        $result = $this->userQueries->checkUserExists('none', 'n@test.com');

        $this->assertTrue($result->success);
        $this->assertFalse($result->data);
        $this->assertEquals(0, $result->affected);
    }

    /**
     * Test: checkUserExists should return failure if execute fails.
     */
    public function testCheckUserExistsFail()
    {
        $error = ['code' => 'err', 'message' => 'DB fail'];
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn($error);

        $result = $this->userQueries->checkUserExists('bad', 'bad@test.com');

        $this->assertFalse($result->success);
        $this->assertEquals($error, $result->error);
    }

    /** ----------------- updateUser ----------------- */
    /**
     * Test: updateUser should modify user details successfully.
     */
    public function testUpdateUserSuccess()
    {
        $user = ['id' => '1', 'username' => 'new', 'email' => 'new@test.com'];
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn($user);

        $result = $this->userQueries->updateUser('1', 'new', 'new@test.com');

        $this->assertTrue($result->success);
        $this->assertEquals($user, $result->data);
    }

    /**
     * Test: updateUser should return failure if execute fails.
     */
    public function testUpdateUserFail()
    {
        $error = ['code' => 'err', 'message' => 'DB fail'];
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn($error);

        $result = $this->userQueries->updateUser('1', 'bad', 'bad@test.com');

        $this->assertFalse($result->success);
        $this->assertEquals($error, $result->error);
    }

    /**
     * Test: updateUser returns ok but no user found (no row updated).
     */
    public function testUpdateUserNotFound()
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn(false);

        $result = $this->userQueries->updateUser('999', 'ghost', 'ghost@test.com');

        $this->assertTrue($result->success);
        $this->assertNull($result->data);
        $this->assertEquals(0, $result->affected);
    }

    /** ----------------- deleteUser ----------------- */
    /**
     * Test: deleteUser should remove user successfully.
     */
    public function testDeleteUserSuccess()
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('rowCount')->willReturn(1);

        $result = $this->userQueries->deleteUser('1');

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->affected);
    }

    /**
     * Test: deleteUser should return failure if execute fails.
     */
    public function testDeleteUserFail()
    {
        $error = ['code' => 'err', 'message' => 'DB fail'];
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn($error);

        $result = $this->userQueries->deleteUser('bad');

        $this->assertFalse($result->success);
        $this->assertEquals($error, $result->error);
    }

    /**
     * Test: deleteUser executes successfully but no rows deleted.
     */
    public function testDeleteUserNotFound()
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('rowCount')->willReturn(0);

        $result = $this->userQueries->deleteUser('999');

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->affected);
        $this->assertNull($result->data);
    }
}
