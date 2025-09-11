<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\SignupService;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use InvalidArgumentException;
use TypeError;

/**
 * Class SignupServiceTypeErrTest
 *
 * Unit tests for SignupService focusing on type errors and invalid input handling.
 *
 * This test suite verifies:
 * - execute() throws TypeError for non-array input or incorrectly typed 'username', 'email', or 'password'
 * - execute() throws InvalidArgumentException for empty, whitespace, or invalid string values
 *
 * Mocks UserQueries, CookieManager, and JwtService to avoid real database, cookie, or JWT operations.
 *
 * @package Tests\Unit\Api\Auth\Service\TypeError
 */
class SignupServiceTypeErrTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private UserQueries $userQueries;

    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject */
    private CookieManager $cookieManager;

    /** @var JwtService&\PHPUnit\Framework\MockObject\MockObject */
    private JwtService $jwt;

    private SignupService $service;

    /**
     * Setup test dependencies and service instance.
     *
     * - Create mocks for UserQueries, CookieManager, and JwtService
     * - Instantiate SignupService with mocks
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userQueries = $this->createMock(UserQueries::class);
        $this->cookieManager = $this->createMock(CookieManager::class);
        $this->jwt = $this->createMock(JwtService::class);

        $this->service = new SignupService(
            $this->userQueries,
            $this->cookieManager,
            $this->jwt
        );
    }

    /**
     * Data provider for testExecuteThrowsTypeError().
     *
     * Supplies invalid input types for signup fields:
     * - input not array
     * - username, email, or password as int, float, array, object
     *
     * @return array<string, mixed>
     */
    public static function invalidTypeProvider(): array
    {
        return [
            'input not array'       => ['not-an-array'],
            'username is int'       => [['username' => 123, 'email' => 'john@example.com', 'password' => 'pass']],
            'username is float'     => [['username' => 3.14, 'email' => 'john@example.com', 'password' => 'pass']],
            'username is array'     => [['username' => ['array'], 'email' => 'john@example.com', 'password' => 'pass']],
            'username is object'    => [['username' => new \stdClass(), 'email' => 'john@example.com', 'password' => 'pass']],
            'email is int'          => [['username' => 'john', 'email' => 123, 'password' => 'pass']],
            'email is float'        => [['username' => 'john', 'email' => 3.14, 'password' => 'pass']],
            'email is array'        => [['username' => 'john', 'email' => ['array'], 'password' => 'pass']],
            'email is object'       => [['username' => 'john', 'email' => new \stdClass(), 'password' => 'pass']],
            'password is int'       => [['username' => 'john', 'email' => 'john@example.com', 'password' => 123]],
            'password is float'     => [['username' => 'john', 'email' => 'john@example.com', 'password' => 3.14]],
            'password is array'     => [['username' => 'john', 'email' => 'john@example.com', 'password' => ['array']]],
            'password is object'    => [['username' => 'john', 'email' => 'john@example.com', 'password' => new \stdClass()]],
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

        // Passing invalid types for username, email, or password should trigger TypeError
        $this->service->execute($input);
    }

    /**
     * Data provider for testExecuteThrowsInvalidArgumentException().
     *
     * Supplies string inputs that are empty, whitespace, or invalid:
     * - username empty or whitespace
     * - email empty, whitespace, or invalid format
     * - password empty or whitespace
     *
     * @return array<string, array>
     */
    public static function invalidArgumentProvider(): array
    {
        return [
            'username empty'      => [['username' => '', 'email' => 'john@example.com', 'password' => 'pass']],
            'username whitespace' => [['username' => '   ', 'email' => 'john@example.com', 'password' => 'pass']],
            'email empty'         => [['username' => 'john', 'email' => '', 'password' => 'pass']],
            'email whitespace'    => [['username' => 'john', 'email' => '   ', 'password' => 'pass']],
            'email invalid'       => [['username' => 'john', 'email' => 'not-an-email', 'password' => 'pass']],
            'password empty'      => [['username' => 'john', 'email' => 'john@example.com', 'password' => '']],
            'password whitespace' => [['username' => 'john', 'email' => 'john@example.com', 'password' => '   ']],
        ];
    }

    /**
     * Test: execute() throws InvalidArgumentException for empty, whitespace, or invalid string fields.
     *
     * @param array $input Input array containing 'username', 'email', and 'password'
     * 
     * @return void
     */
    #[DataProvider('invalidArgumentProvider')]
    public function testExecuteThrowsInvalidArgumentException(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Empty, whitespace, or invalid email/password triggers InvalidArgumentException
        $this->service->execute($input);
    }
}
