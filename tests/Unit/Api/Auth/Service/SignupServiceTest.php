<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\SignupService;
use App\DB\QueryResult;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class SignupServiceTest
 *
 * Unit tests for SignupService.
 *
 * This test suite verifies:
 * - Validation of input (username, email, password)
 * - Handling of DB query failures in checkUserExists and createUser
 * - Handling of existing username/email
 * - Successful signup flow (create user, create JWT, set cookie)
 *
 * @package Tests\Unit\Api\Auth\Service
 */
class SignupServiceTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private UserQueries $userQueries;

    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject */
    private CookieManager $cookieManager;

    /** @var JwtService&\PHPUnit\Framework\MockObject\MockObject */
    private JwtService $jwt;

    private SignupService $service;

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

        $this->service = new SignupService(
            $this->userQueries,
            $this->cookieManager,
            $this->jwt
        );
    }

    /**
     * Data provider for invalid signup input.
     *
     * @return array<string, array>
     */
    public static function invalidInputProvider(): array
    {
        return [
            'missing username'  => [[]],
            'empty username'    => [['username' => '', 'email' => 'john@example.com', 'password' => 'pass']],
            'missing email'     => [['username' => 'john', 'password' => 'pass']],
            'empty email'       => [['username' => 'john', 'email' => '', 'password' => 'pass']],
            'invalid email'     => [['username' => 'john', 'email' => 'not-an-email', 'password' => 'pass']],
            'missing password'  => [['username' => 'john', 'email' => 'john@example.com']],
            'empty password'    => [['username' => 'john', 'email' => 'john@example.com', 'password' => '']],
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
    public function testExecuteThrowsInvalidArgumentException(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Act: call service with invalid input
        $this->service->execute($input);
    }

    /**
     * Data provider for DB failures in checkUserExists.
     *
     * @return array<string, array>
     */
    public static function checkUserExistsFailProvider(): array
    {
        return [
            'with error info' => [
                QueryResult::fail(['SQLSTATE[HY000]', 'Some error']),
                'Failed to check user existence: SQLSTATE[HY000] | Some error'
            ],
            'without error' => [
                QueryResult::fail(null),
                'Failed to check user existence: Unknown error'
            ],
        ];
    }

    /**
     * Test that execute() throws RuntimeException when checkUserExists fails.
     *
     * @param QueryResult $result
     * @param string      $expectedMessage
     *
     * @return void
     */
    #[DataProvider('checkUserExistsFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenCheckUserExistsFails(QueryResult $result, string $expectedMessage): void
    {
        // Mock DB failure
        $this->userQueries->method('checkUserExists')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        // Act
        $this->service->execute(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
    }

    /**
     * Test that execute() throws InvalidArgumentException when user already exists.
     *
     * @return void
     */
    public function testExecuteThrowsInvalidArgumentExceptionWhenUserExists(): void
    {
        // Mock existing user
        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(true, 1));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Username or email already exists.');

        // Act
        $this->service->execute(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
    }

    /**
     * Data provider for DB failures in createUser.
     *
     * @return array<string, array>
     */
    public static function createUserFailProvider(): array
    {
        return [
            'fail with error info' => [
                QueryResult::fail(['SQLSTATE[HY000]', 'Insert error']),
                'Failed to sign up: SQLSTATE[HY000] | Insert error'
            ],
            'fail without error' => [
                QueryResult::fail(null),
                'Failed to sign up: Unknown error'
            ],
        ];
    }

    /**
     * Test that execute() throws RuntimeException when createUser fails.
     *
     * @param QueryResult $result
     * @param string      $expectedMessage
     *
     * @return void
     */
    #[DataProvider('createUserFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenCreateUserFails(QueryResult $result, string $expectedMessage): void
    {
        // Ensure user does not exist
        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 0));
        // Mock createUser failure
        $this->userQueries->method('createUser')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        // Act
        $this->service->execute(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
    }

    /**
     * Test that execute() throws RuntimeException when createUser affects 0 rows.
     *
     * @return void
     */
    public function testExecuteThrowsRuntimeExceptionWhenCreateUserHasNoChanges(): void
    {
        $userData = ['id' => 'uuid', 'username' => 'john', 'email' => 'john@example.com'];
        $result = QueryResult::ok($userData, 0); // success but affected = 0

        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 0));
        $this->userQueries->method('createUser')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to sign up: Unknown error');

        // Act
        $this->service->execute(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
    }

    /**
     * Test that execute() creates JWT and sets cookie when signup is successful.
     *
     * @return void
     */
    public function testExecuteCallsJwtAndCookieManagerWhenSuccessful(): void
    {
        $userData = ['id' => 'uuid', 'username' => 'john', 'email' => 'john@example.com'];
        $result = QueryResult::ok($userData, 1);

        // Ensure user does not exist
        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 0));
        // Mock successful user creation
        $this->userQueries->method('createUser')->willReturn($result);

        // Expect JWT creation
        $this->jwt->expects($this->once())
            ->method('create')
            ->with(['id' => 'uuid'])
            ->willReturn('mock-token');

        // Expect cookie set with JWT
        $this->cookieManager->expects($this->once())
            ->method('setAccessToken')
            ->with('mock-token', $this->anything());

        // Act
        $this->service->execute(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
    }
}
