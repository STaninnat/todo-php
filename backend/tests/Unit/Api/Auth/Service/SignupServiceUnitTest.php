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
use App\Api\Auth\Service\RefreshTokenService;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class SignupServiceTest
 *
 * Unit tests for the SignupService class.
 *
 * Covers the following behaviors:
 * - Input validation and exception handling
 * - Behavior when checking existing users
 * - Error propagation from query failures
 * - JWT creation and cookie setting upon successful signup
 *
 * @package Tests\Unit\Api\Auth\Service
 */
class SignupServiceUnitTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject Mock for database user queries. */
    private UserQueries $userQueries;

    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject Mock for handling access token cookies. */
    private CookieManager $cookieManager;

    /** @var JwtService&\PHPUnit\Framework\MockObject\MockObject Mock for JWT token service. */
    private JwtService $jwt;

    /** @var RefreshTokenService&\PHPUnit\Framework\MockObject\MockObject Mock for Refresh token service. */
    private RefreshTokenService $refreshTokenService;

    /** @var SignupService Service under test. */
    private SignupService $service;

    use ApiTestHelperTrait;

    /**
     * Setup before each test.
     *
     * Initializes mock dependencies and injects them into SignupService.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock instances for dependencies
        $this->userQueries = $this->createMock(UserQueries::class);
        $this->cookieManager = $this->createMock(CookieManager::class);
        $this->jwt = $this->createMock(JwtService::class);
        $this->refreshTokenService = $this->createMock(RefreshTokenService::class);

        // Inject mocks into the service
        $this->service = new SignupService(
            $this->userQueries,
            $this->cookieManager,
            $this->jwt,
            $this->refreshTokenService
        );
    }

    /**
     * Provides invalid input cases for SignupService::execute().
     *
     * Tests missing or malformed fields (username, email, password).
     *
     * @return array<string, array{0: array<string,mixed>}>
     */
    public static function invalidInputProvider(): array
    {
        return [
            'missing username' => [[]],
            'empty username' => [['username' => '', 'email' => 'john@example.com', 'password' => 'pass']],
            'missing email' => [['username' => 'john', 'password' => 'pass']],
            'empty email' => [['username' => 'john', 'email' => '', 'password' => 'pass']],
            'invalid email' => [['username' => 'john', 'email' => 'not-an-email', 'password' => 'pass']],
            'missing password' => [['username' => 'john', 'email' => 'john@example.com']],
            'empty password' => [['username' => 'john', 'email' => 'john@example.com', 'password' => '']],
        ];
    }

    /**
     * Ensures that execute() throws InvalidArgumentException
     * when provided with invalid or missing input fields.
     *
     * @param array<string,mixed> $input Test input data.
     *
     * @return void
     */
    #[DataProvider('invalidInputProvider')]
    public function testExecuteThrowsInvalidArgumentException(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var array<string,mixed> $input */
        $req = $this->makeRequest($input);
        $this->service->execute($req);
    }

    /**
     * Provides simulated failure responses for user existence checks.
     *
     * Covers scenarios where database query fails with or without error info.
     *
     * @return array<string, array{0:QueryResult,1:string}>
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
                'Failed to check user existence: Unknown database error.'
            ],
        ];
    }

    /**
     * Tests that execute() throws RuntimeException when
     * checkUserExists() query fails.
     *
     * @param QueryResult $result          Simulated failure result.
     * @param string      $expectedMessage Expected exception message.
     *
     * @return void
     */
    #[DataProvider('checkUserExistsFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenCheckUserExistsFails(QueryResult $result, string $expectedMessage): void
    {
        // Simulate database failure during user existence check
        $this->userQueries->method('checkUserExists')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
        $this->service->execute($req);
    }

    /**
     * Ensures that execute() throws InvalidArgumentException when
     * user already exists (duplicate username or email).
     *
     * @return void
     */
    public function testExecuteThrowsInvalidArgumentExceptionWhenUserExists(): void
    {
        // Simulate user already exists
        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(true, 1));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Username or email already exists.');

        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
        $this->service->execute($req);
    }

    /**
     * Provides failure results for user creation attempts.
     *
     * Covers both error and no-error failure results.
     *
     * @return array<string, array{0:QueryResult,1:string}>
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
                'Failed to sign up: Unknown database error.'
            ],
        ];
    }

    /**
     * Tests that execute() throws RuntimeException when createUser() fails.
     *
     * @param QueryResult $result          Simulated failure result.
     * @param string      $expectedMessage Expected exception message.
     *
     * @return void
     */
    #[DataProvider('createUserFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenCreateUserFails(QueryResult $result, string $expectedMessage): void
    {
        // Simulate success in checking user existence, but failure in creating user
        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 0));
        $this->userQueries->method('createUser')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
        $this->service->execute($req);
    }

    /**
     * Tests that execute() throws RuntimeException when user creation
     * returns success but no rows are affected.
     *
     * @return void
     */
    public function testExecuteThrowsRuntimeExceptionWhenCreateUserHasNoChanges(): void
    {
        // Simulate DB returning OK but zero affected rows
        $userData = ['id' => 'uuid', 'username' => 'john', 'email' => 'john@example.com'];
        $result = QueryResult::ok($userData, 0);

        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 0));
        $this->userQueries->method('createUser')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to sign up: No data or changes found.');

        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
        $this->service->execute($req);
    }

    /**
     * Ensures that on successful user creation, the service:
     * - Creates a JWT token
     * - Stores it via CookieManager
     * - Creates a Refresh token
     * - Stores it via CookieManager
     *
     * @return void
     */
    public function testExecuteCallsJwtAndCookieManagerWhenSuccessful(): void
    {
        // Mock successful user creation and JWT flow
        $userData = ['id' => 'uuid', 'username' => 'john', 'email' => 'john@example.com'];
        $result = QueryResult::ok($userData, 1);

        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 1));
        $this->userQueries->method('createUser')->willReturn($result);

        // Expect JWT service to create token
        $this->jwt->expects($this->once())
            ->method('create')
            ->with(['id' => 'uuid'])
            ->willReturn('mock-token');

        // Expect RefreshTokenService to create refresh token
        $this->refreshTokenService->expects($this->once())
            ->method('create')
            ->with('uuid', 604800)
            ->willReturn('mock-refresh-token');

        // Expect cookie manager to store both tokens
        $this->cookieManager->expects($this->once())
            ->method('setAccessToken')
            ->with('mock-token', $this->anything());

        $this->cookieManager->expects($this->once())
            ->method('setRefreshToken')
            ->with('mock-refresh-token', $this->anything());

        // Simulate request execution
        $req = $this->makeRequest(['username' => 'john', 'email' => 'john@example.com', 'password' => 'pass']);
        $this->service->execute($req);
    }
}
