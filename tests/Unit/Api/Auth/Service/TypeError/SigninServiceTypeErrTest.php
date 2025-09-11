<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\SigninService;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use TypeError;
use InvalidArgumentException;

/**
 * Class SigninServiceTypeErrTest
 *
 * Unit tests for SigninService focusing on type errors and invalid input handling.
 *
 * This test suite verifies:
 * - execute() throws TypeError for non-array input or incorrectly typed 'username' or 'password'
 * - execute() throws InvalidArgumentException for empty or whitespace 'username' or 'password'
 *
 * Mocks UserQueries, CookieManager, and JwtService to avoid real database, cookie, or JWT operations.
 *
 * @package Tests\Unit\Api\Auth\Service\TypeError
 */
class SigninServiceTypeErrTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private UserQueries $userQueries;

    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject */
    private CookieManager $cookieManager;

    /** @var JwtService&\PHPUnit\Framework\MockObject\MockObject */
    private JwtService $jwt;

    private SigninService $service;

    /**
     * Setup test dependencies and service instance.
     *
     * - Create mocks for UserQueries, CookieManager, and JwtService
     * - Instantiate SigninService with mocks
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userQueries = $this->createMock(UserQueries::class);
        $this->cookieManager = $this->createMock(CookieManager::class);
        $this->jwt = $this->createMock(JwtService::class);

        $this->service = new SigninService(
            $this->userQueries,
            $this->cookieManager,
            $this->jwt
        );
    }

    /**
     * Data provider for testExecuteThrowsTypeError().
     *
     * Supplies invalid input types for username/password:
     * - input not array
     * - username as int, float, array, object
     * - password as int, float, array, object
     *
     * @return array<string, mixed>
     */
    public static function invalidTypeProvider(): array
    {
        return [
            'input not array' => ['not-an-array'],
            'username int'    => [['username' => 123, 'password' => 'pass']],
            'username float'  => [['username' => 3.14, 'password' => 'pass']],
            'username array'  => [['username' => ['array'], 'password' => 'pass']],
            'username object' => [['username' => new \stdClass(), 'password' => 'pass']],
            'password int'    => [['username' => 'john', 'password' => 123]],
            'password float'  => [['username' => 'john', 'password' => 3.14]],
            'password array'  => [['username' => 'john', 'password' => ['array']]],
            'password object' => [['username' => 'john', 'password' => new \stdClass()]],
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

        // Passing invalid types for username/password should trigger TypeError
        $this->service->execute($input);
    }

    /**
     * Data provider for testExecuteThrowsInvalidArgumentException().
     *
     * Supplies string inputs that are empty or whitespace:
     * - username empty or whitespace
     * - password empty or whitespace
     *
     * @return array<string, array>
     */
    public static function invalidArgumentProvider(): array
    {
        return [
            'username empty'      => [['username' => '', 'password' => 'pass']],
            'username whitespace' => [['username' => '   ', 'password' => 'pass']],
            'password empty'      => [['username' => 'john', 'password' => '']],
            'password whitespace' => [['username' => 'john', 'password' => '   ']],
        ];
    }

    /**
     * Test: execute() throws InvalidArgumentException for empty or whitespace username/password.
     *
     * @param array $input Input array containing 'username' and 'password'
     * 
     * @return void
     */
    #[DataProvider('invalidArgumentProvider')]
    public function testExecuteThrowsInvalidArgumentException(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Empty or whitespace username/password triggers InvalidArgumentException
        $this->service->execute($input);
    }
}
