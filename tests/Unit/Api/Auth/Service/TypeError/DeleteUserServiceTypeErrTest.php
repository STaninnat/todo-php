<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use PHPUnit\Framework\TestCase;
use App\Api\Auth\Service\DeleteUserService;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use InvalidArgumentException;
use TypeError;

/**
 * Class DeleteUserServiceTypeErrTest
 *
 * Unit tests for DeleteUserService focusing on type errors and invalid input handling.
 *
 * This test suite verifies:
 * - execute() throws TypeError for non-array or incorrectly typed 'user_id'
 * - execute() throws InvalidArgumentException when 'user_id' is missing or empty
 *
 * Mocks UserQueries and CookieManager to avoid real DB or cookie operations.
 *
 * @package Tests\Unit\Api\Auth\Service\TypeError
 */
class DeleteUserServiceTypeErrTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $userQueriesMock;

    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject */
    private $cookieManagerMock;

    private DeleteUserService $service;

    /**
     * Setup test dependencies and service instance.
     *
     * - Create mocks for UserQueries and CookieManager
     * - Instantiate DeleteUserService with mocks
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userQueriesMock = $this->createMock(UserQueries::class);
        $this->cookieManagerMock = $this->createMock(CookieManager::class);

        $this->service = new DeleteUserService(
            $this->userQueriesMock,
            $this->cookieManagerMock
        );
    }

    /**
     * Test: execute() throws TypeError if input is not an array.
     *
     * @return void
     */
    public function testExecuteThrowsTypeErrorWhenInputNotArray(): void
    {
        $this->expectException(TypeError::class);

        // Passing a string instead of an array should cause TypeError
        /** @phpstan-ignore-next-line */
        $this->service->execute('not-an-array');
    }

    /**
     * Test: execute() throws TypeError if 'user_id' is an object.
     *
     * @return void
     */
    public function testExecuteThrowsTypeErrorWhenUserIdIsObject(): void
    {
        $this->expectException(TypeError::class);

        // 'user_id' must be a valid scalar value, object is invalid
        $this->service->execute(['user_id' => new \stdClass()]);
    }

    /**
     * Test: execute() throws TypeError if 'user_id' is an array.
     *
     * @return void
     */
    public function testExecuteThrowsTypeErrorWhenUserIdIsArray(): void
    {
        $this->expectException(TypeError::class);

        // Array as user_id is invalid and should trigger TypeError
        $this->service->execute(['user_id' => ['array']]);
    }

    /**
     * Test: execute() throws TypeError if 'user_id' is a float.
     *
     * @return void
     */
    public function testExecuteThrowsTypeErrorWhenUserIdIsFloat(): void
    {
        $this->expectException(TypeError::class);

        // Float is not an acceptable type for user_id
        $this->service->execute(['user_id' => 3.14]);
    }

    /**
     * Test: execute() throws InvalidArgumentException if 'user_id' is null.
     *
     * @return void
     */
    public function testExecuteThrowsInvalidArgumentExceptionWhenUserIdNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required.');

        // Null user_id should trigger a specific invalid argument exception
        $this->service->execute(['user_id' => null]);
    }

    /**
     * Test: execute() throws InvalidArgumentException if 'user_id' is empty string.
     *
     * @return void
     */
    public function testExecuteThrowsInvalidArgumentExceptionWhenUserIdEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required.');

        // Empty string is considered invalid input
        $this->service->execute(['user_id' => '']);
    }

    /**
     * Test: execute() throws InvalidArgumentException if 'user_id' is whitespace.
     *
     * @return void
     */
    public function testExecuteThrowsInvalidArgumentExceptionWhenUserIdWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required.');

        // Only whitespace should be treated as missing user_id
        $this->service->execute(['user_id' => '   ']);
    }

    /**
     * Test: execute() throws TypeError even if 'user_id' is an integer.
     *
     * @return void
     */
    public function testExecuteThrowsTypeErrorWhenUserIdIsInt(): void
    {
        $this->expectException(TypeError::class);

        // Despite being numeric, integer type triggers TypeError in this service
        $this->service->execute(['user_id' => 123]);
    }
}
