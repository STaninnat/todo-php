<?php

declare(strict_types=1);

namespace Tests\Unit\DB\TypeError;

use PHPUnit\Framework\TestCase;
use App\DB\UserQueries;
use PDO;

/**
 * Class UserQueriesTypeErrTest
 *
 * Unit tests for UserQueries to ensure strict typing enforcement.
 * Tests that TypeError is thrown when methods receive invalid argument types.
 *
 * @package Tests\Unit\DB\TypeError
 */
class UserQueriesTypeErrTest extends TestCase
{
    /**
     * @var UserQueries UserQueries instance used for testing
     */
    private UserQueries $userQueries;

    /**
     * Set up UserQueries instance with a mocked PDO before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $pdo = $this->createMock(PDO::class);
        $this->userQueries = new UserQueries($pdo);
    }

    /**
     * Test createUser() throws TypeError when all parameters have invalid types.
     *
     * @return void
     */
    public function testCreateUserInvalidTypes(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the parameter all wrong types will cause a TypeError.
        /** @phpstan-ignore-next-line */
        $this->userQueries->createUser(123, [], null, true);
    }

    /**
     * Test getUserByName() throws TypeError when username is not a string.
     *
     * @return void
     */
    public function testGetUserByNameInvalidType(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the username as an int instead of a string will cause a TypeError.
        /** @phpstan-ignore-next-line */
        $this->userQueries->getUserByName(123);
    }

    /**
     * Test getUserByID() throws TypeError when id is not a string.
     *
     * @return void
     */
    public function testGetUserByIDInvalidType(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the id as an int instead of a string will cause a TypeError.
        /** @phpstan-ignore-next-line */
        $this->userQueries->getUserByID(456);
    }

    /**
     * Test checkUserExists() throws TypeError when parameters are not strings.
     *
     * @return void
     */
    public function testCheckUserExistsInvalidTypes(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the title as an array, null instead of a string will cause a TypeError.
        /** @phpstan-ignore-next-line */
        $this->userQueries->checkUserExists([], null);
    }

    /**
     * Test updateUser() throws TypeError when parameters are of invalid types.
     *
     * @return void
     */
    public function testUpdateUserInvalidTypes(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the parameter all wrong types will cause a TypeError.
        /** @phpstan-ignore-next-line */
        $this->userQueries->updateUser(1, true, []);
    }

    /**
     * Test deleteUser() throws TypeError when id is not a string.
     *
     * @return void
     */
    public function testDeleteUserInvalidType(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the id as an int instead of a string will cause a TypeError.
        /** @phpstan-ignore-next-line */
        $this->userQueries->deleteUser(999);
    }
}
