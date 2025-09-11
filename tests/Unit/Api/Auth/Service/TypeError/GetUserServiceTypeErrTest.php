<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\GetUserService;
use App\DB\UserQueries;
use InvalidArgumentException;
use TypeError;

/**
 * Class GetUserServiceTypeErrTest
 *
 * Unit tests for GetUserService focusing on type errors and invalid input handling.
 *
 * This test suite verifies:
 * - execute() throws TypeError for non-array input or incorrectly typed 'user_id'
 * - execute() throws InvalidArgumentException for missing or empty string 'user_id'
 *
 * Mocks UserQueries to avoid real database operations.
 *
 * @package Tests\Unit\Api\Auth\Service\TypeError
 */
class GetUserServiceTypeErrTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $userQueriesMock;

    private GetUserService $service;

    /**
     * Setup test dependencies and service instance.
     *
     * - Create mock for UserQueries
     * - Instantiate GetUserService with mock
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userQueriesMock = $this->createMock(UserQueries::class);
        $this->service = new GetUserService($this->userQueriesMock);
    }

    /**
     * Data provider for testExecuteThrowsTypeError().
     *
     * Supplies various invalid input types:
     * - input not an array
     * - 'user_id' as object, array, float, int
     *
     * @return array<string, mixed>
     */
    public static function invalidTypeProvider(): array
    {
        return [
            'input not array'   => ['not-an-array'],
            'user_id is object' => [['user_id' => new \stdClass()]],
            'user_id is array'  => [['user_id' => ['array']]],
            'user_id is float'  => [['user_id' => 3.14]],
            'user_id is int'    => [['user_id' => 123]],
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

        // Passing invalid types should trigger TypeError
        $this->service->execute($input);
    }

    /**
     * Data provider for testExecuteThrowsInvalidArgumentException().
     *
     * Supplies string inputs that are considered invalid:
     * - null
     * - empty string
     * - whitespace string
     *
     * @return array<string, array>
     */
    public static function invalidArgumentProvider(): array
    {
        return [
            'user_id is null'       => [['user_id' => null]],
            'user_id empty string'  => [['user_id' => '']],
            'user_id whitespace'    => [['user_id' => '   ']],
        ];
    }

    /**
     * Test: execute() throws InvalidArgumentException for missing or empty 'user_id'.
     *
     * @param array $input Input array containing 'user_id'
     * 
     * @return void
     */
    #[DataProvider('invalidArgumentProvider')]
    public function testExecuteThrowsInvalidArgumentException(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required.');

        // Passing null, empty, or whitespace user_id triggers InvalidArgumentException
        $this->service->execute($input);
    }
}
