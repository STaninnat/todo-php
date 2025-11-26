<?php

declare(strict_types=1);

namespace Tests\Unit\DB;

use PHPUnit\Framework\TestCase;
use App\DB\UserQueries;
use PDOStatement;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class UserQueriesTest
 *
 * Unit tests for the UserQueries class.
 *
 * This test suite verifies CRUD and user management operations:
 * - createUser(), getUserByName(), getUserByID()
 * - checkUserExists(), updateUser(), deleteUser()
 *
 * Uses PDOStatement and PDO mocks to avoid real database connections.
 *
 * @package Tests\Unit\DB
 */
class UserQueriesUnitTest extends TestCase
{
    /** @var PDO&MockObject */
    private PDO $pdo;

    /** @var PDOStatement&MockObject */
    private PDOStatement $stmt;

    private UserQueries $userQueries;

    /**
     * Setup mocks for PDO and PDOStatement before each test.
     *
     * Mocks execute, fetch, fetchAll, rowCount, and fetchColumn methods,
     * then injects the mocked PDO into UserQueries instance.
     *
     * @return void
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
     * 
     * @return void
     */
    public function testCreateUserSuccess(): void
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
     * 
     * @return void
     */
    public function testCreateUserFail(): void
    {
        $error = ['123', 'DB error'];
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn($error);

        $result = $this->userQueries->createUser('1', 'fail', 'f@test.com', 'pass');

        $this->assertFalse($result->success);
        $this->assertEquals($error, $result->error);
    }

    /**
     * Test: createUser returns ok but getUserByID fetch returns null (no user found).
     * 
     * @return void
     */
    public function testCreateUserButGetUserByIdReturnsNull(): void
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
     * 
     * @return void
     */
    public function testGetUserByNameFound(): void
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
     * 
     * @return void
     */
    public function testGetUserByNameNotFound(): void
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
     * 
     * @return void
     */
    public function testGetUserByNameFail(): void
    {
        $error = ['err', 'DB fail'];
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn($error);

        $result = $this->userQueries->getUserByName('bad');

        $this->assertFalse($result->success);
        $this->assertEquals($error, $result->error);
    }

    /** ----------------- getUserByID ----------------- */
    /**
     * Test: getUserByID should return user when found.
     * 
     * @return void
     */
    public function testGetUserByIDFound(): void
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
     * 
     * @return void
     */
    public function testGetUserByIDNotFound(): void
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
     * 
     * @return void
     */
    public function testGetUserByIDFail(): void
    {
        $error = ['err', 'DB fail'];
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn($error);

        $result = $this->userQueries->getUserByID('failid');

        $this->assertFalse($result->success);
        $this->assertEquals($error, $result->error);
    }

    /** ----------------- checkUserExists ----------------- */
    /**
     * Test: checkUserExists should return true if a user exists.
     * 
     * @return void
     */
    public function testCheckUserExistsTrue(): void
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
     * 
     * @return void
     */
    public function testCheckUserExistsFalse(): void
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
     * 
     * @return void
     */
    public function testCheckUserExistsFail(): void
    {
        $error = ['err', 'DB fail'];
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn($error);

        $result = $this->userQueries->checkUserExists('bad', 'bad@test.com');

        $this->assertFalse($result->success);
        $this->assertEquals($error, $result->error);
    }

    /** ----------------- updateUser ----------------- */
    /**
     * Test: updateUser should modify user details successfully.
     * 
     * @return void
     */
    public function testUpdateUserSuccess(): void
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
     * 
     * @return void
     */
    public function testUpdateUserFail(): void
    {
        $error = ['err', 'DB fail'];
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn($error);

        $result = $this->userQueries->updateUser('1', 'bad', 'bad@test.com');

        $this->assertFalse($result->success);
        $this->assertEquals($error, $result->error);
    }

    /**
     * Test: updateUser returns ok but no user found (no row updated).
     * 
     * @return void
     */
    public function testUpdateUserNotFound(): void
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
     * 
     * @return void
     */
    public function testDeleteUserSuccess(): void
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('rowCount')->willReturn(1);

        $result = $this->userQueries->deleteUser('1');

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->affected);
    }

    /**
     * Test: deleteUser should return failure if execute fails.
     * 
     * @return void
     */
    public function testDeleteUserFail(): void
    {
        $error = ['err', 'DB fail'];
        $this->stmt->method('execute')->willReturn(false);
        $this->stmt->method('errorInfo')->willReturn($error);

        $result = $this->userQueries->deleteUser('bad');

        $this->assertFalse($result->success);
        $this->assertEquals($error, $result->error);
    }

    /**
     * Test: deleteUser executes successfully but no rows deleted.
     * 
     * @return void
     */
    public function testDeleteUserNotFound(): void
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('rowCount')->willReturn(0);

        $result = $this->userQueries->deleteUser('999');

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->affected);
        $this->assertNull($result->data);
    }
}
