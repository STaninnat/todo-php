<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\SigninService;
use App\DB\QueryResult;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class SigninServiceTest
 *
 * Unit tests for SigninService.
 *
 * This test suite verifies:
 * - Validation of input (username/password)
 * - Handling of DB query failures
 * - Handling of invalid credentials
 * - Successful signin flow (JWT creation + cookie set)
 *
 * @package Tests\Unit\Api\Auth\Service
 */
class SigninServiceTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private UserQueries $userQueries;

    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject */
    private CookieManager $cookieManager;

    /** @var JwtService&\PHPUnit\Framework\MockObject\MockObject */
    private JwtService $jwt;

    private SigninService $service;

    /**
     * Setup mocks and service instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

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
     * Data provider for invalid signin input.
     *
     * @return array<string, array>
     */
    public static function invalidInputProvider(): array
    {
        return [
            'missing username' => [['password' => 'pass']],
            'empty username'   => [['username' => '', 'password' => 'pass']],
            'missing password' => [['username' => 'john']],
            'empty password'   => [['username' => 'john', 'password' => '']],
        ];
    }

    /**
     * Test that execute() throws InvalidArgumentException for invalid input.
     *
     * @param array $input
     *
     * @return void
     */
    #[DataProvider('invalidInputProvider')]
    public function testExecuteThrowsInvalidArgumentExceptionForInvalidInput(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Act: call service with missing or empty username/password
        $this->service->execute($input);
    }

    /**
     * Data provider for DB query failures.
     *
     * @return array<string, array>
     */
    public static function getUserByNameFailProvider(): array
    {
        return [
            'with error info' => [
                QueryResult::fail(['SQLSTATE[HY000]', 'Some error']),
                'Failed to fetch user: SQLSTATE[HY000] | Some error'
            ],
            'without error' => [
                QueryResult::fail(null),
                'Failed to fetch user: Unknown error'
            ],
        ];
    }

    /**
     * Test that execute() throws RuntimeException when getUserByName fails.
     *
     * @param QueryResult $result
     * @param string      $expectedMessage
     *
     * @return void
     */
    #[DataProvider('getUserByNameFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenGetUserByNameFails(QueryResult $result, string $expectedMessage): void
    {
        // Mock DB call to simulate failure
        $this->userQueries->method('getUserByName')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        // Act
        $this->service->execute(['username' => 'john', 'password' => 'pass']);
    }

    /**
     * Test that execute() throws InvalidArgumentException when user not found.
     *
     * @return void
     */
    public function testExecuteThrowsInvalidArgumentExceptionWhenUserNotFound(): void
    {
        // Mock DB returning no rows
        $this->userQueries->method('getUserByName')->willReturn(QueryResult::ok(null, 0));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid username or password.');

        // Act
        $this->service->execute(['username' => 'john', 'password' => 'pass']);
    }

    /**
     * Test that execute() throws InvalidArgumentException for wrong password.
     *
     * @return void
     */
    public function testExecuteThrowsInvalidArgumentExceptionForWrongPassword(): void
    {
        // Mock DB returning a user with hashed password
        $user = ['id' => 'uuid', 'username' => 'john', 'password' => password_hash('correct-pass', PASSWORD_DEFAULT)];
        $this->userQueries->method('getUserByName')->willReturn(QueryResult::ok($user, 1));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid username or password.');

        // Act with wrong password
        $this->service->execute(['username' => 'john', 'password' => 'wrong-pass']);
    }

    /**
     * Test that execute() creates JWT and sets cookie when successful.
     *
     * @return void
     */
    public function testExecuteCallsJwtAndCookieManagerWhenSuccessful(): void
    {
        // Mock DB returning a valid user
        $user = ['id' => 'uuid', 'username' => 'john', 'password' => password_hash('pass123', PASSWORD_DEFAULT)];
        $this->userQueries->method('getUserByName')->willReturn(QueryResult::ok($user, 1));

        // Expect JWT creation with user id
        $this->jwt->expects($this->once())
            ->method('create')
            ->with(['id' => 'uuid'])
            ->willReturn('mock-token');

        // Expect cookie set with JWT
        $this->cookieManager->expects($this->once())
            ->method('setAccessToken')
            ->with('mock-token', $this->anything());

        // Act
        $this->service->execute(['username' => 'john', 'password' => 'pass123']);
    }
}
