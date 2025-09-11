<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\UpdateUserService;
use App\DB\UserQueries;
use InvalidArgumentException;
use TypeError;

/**
 * Class UpdateUserServiceTypeErrTest
 *
 * Unit tests for UpdateUserService focusing on type errors and invalid input handling.
 *
 * This test suite verifies:
 * - execute() throws TypeError for non-array input or incorrectly typed 'user_id', 'username', or 'email'
 * - execute() throws InvalidArgumentException for empty, whitespace, null, or invalid string values
 *
 * Mocks UserQueries to avoid real database operations.
 *
 * @package Tests\Unit\Api\Auth\Service\TypeError
 */
class UpdateUserServiceTypeErrTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $userQueriesMock;

    private UpdateUserService $service;

    /**
     * Setup test dependencies and service instance.
     *
     * - Create mock for UserQueries
     * - Instantiate UpdateUserService with mock
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userQueriesMock = $this->createMock(UserQueries::class);
        $this->service = new UpdateUserService($this->userQueriesMock);
    }

    /**
     * Data provider for testExecuteThrowsTypeError().
     *
     * Supplies invalid input types for update fields:
     * - input not array
     * - 'user_id', 'username', or 'email' as object, array, float, int
     *
     * @return array<string, mixed>
     */
    public static function invalidTypeProvider(): array
    {
        return [
            'input not array'       => ['not-an-array'],
            'user_id is object'     => [['user_id' => new \stdClass(), 'username' => 'john', 'email' => 'john@example.com']],
            'user_id is array'      => [['user_id' => ['array'], 'username' => 'john', 'email' => 'john@example.com']],
            'user_id is float'      => [['user_id' => 3.14, 'username' => 'john', 'email' => 'john@example.com']],
            'user_id is int'        => [['user_id' => 123, 'username' => 'john', 'email' => 'john@example.com']],
            'username is int'       => [['user_id' => '1', 'username' => 123, 'email' => 'john@example.com']],
            'username is float'     => [['user_id' => '1', 'username' => 3.14, 'email' => 'john@example.com']],
            'username is array'     => [['user_id' => '1', 'username' => ['array'], 'email' => 'john@example.com']],
            'username is object'    => [['user_id' => '1', 'username' => new \stdClass(), 'email' => 'john@example.com']],
            'email is int'          => [['user_id' => '1', 'username' => 'john', 'email' => 123]],
            'email is float'        => [['user_id' => '1', 'username' => 'john', 'email' => 3.14]],
            'email is array'        => [['user_id' => '1', 'username' => 'john', 'email' => ['array']]],
            'email is object'       => [['user_id' => '1', 'username' => 'john', 'email' => new \stdClass()]],
        ];
    }

    /**
     * Test: execute() throws TypeError for invalid input types.
     *
     * @param mixed $input Input to be passed to execute()
     * 
     * @return void
     */
    #[DataProvider('invalidTypeProvider')]
    public function testExecuteThrowsTypeError(mixed $input): void
    {
        $this->expectException(TypeError::class);

        // Passing invalid types for user_id, username, or email should trigger TypeError
        $this->service->execute($input);
    }

    /**
     * Data provider for testExecuteThrowsInvalidArgumentException().
     *
     * Supplies string inputs that are null, empty, whitespace, or invalid:
     * - 'user_id' null, empty, or whitespace
     * - 'username' empty or whitespace
     * - 'email' empty, whitespace, or invalid format
     *
     * @return array<string, array>
     */
    public static function invalidArgumentProvider(): array
    {
        return [
            'user_id null'        => [['user_id' => null, 'username' => 'john', 'email' => 'john@example.com']],
            'user_id empty'       => [['user_id' => '', 'username' => 'john', 'email' => 'john@example.com']],
            'user_id whitespace'  => [['user_id' => '   ', 'username' => 'john', 'email' => 'john@example.com']],
            'username empty'      => [['user_id' => '1', 'username' => '', 'email' => 'john@example.com']],
            'username whitespace' => [['user_id' => '1', 'username' => '   ', 'email' => 'john@example.com']],
            'email empty'         => [['user_id' => '1', 'username' => 'john', 'email' => '']],
            'email whitespace'    => [['user_id' => '1', 'username' => 'john', 'email' => '   ']],
            'email invalid'       => [['user_id' => '1', 'username' => 'john', 'email' => 'not-an-email']],
        ];
    }

    /**
     * Test: execute() throws InvalidArgumentException for null, empty, whitespace, or invalid string fields.
     *
     * @param array $input Input array containing 'user_id', 'username', and 'email'
     * 
     * @return void
     */
    #[DataProvider('invalidArgumentProvider')]
    public function testExecuteThrowsInvalidArgumentException(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Null, empty, whitespace, or invalid email triggers InvalidArgumentException
        $this->service->execute($input);
    }
}
