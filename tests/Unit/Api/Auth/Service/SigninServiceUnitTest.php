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
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class SigninServiceTest
 *
 * Unit tests for the SigninService class, which handles
 * user authentication logic during sign-in requests.
 *
 * Covers scenarios such as:
 * - Missing or invalid input fields
 * - Database query failures
 * - User not found
 * - Incorrect passwords
 * - Successful authentication flow with JWT + cookies
 *
 * Uses data providers to handle various invalid input and
 * query failure cases efficiently.
 *
 * @package Tests\Unit\Api\Auth\Service
 */
class SigninServiceUnitTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject Mocked dependency for DB access */
    private UserQueries $userQueries;

    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject Mocked dependency for cookie handling */
    private CookieManager $cookieManager;

    /** @var JwtService&\PHPUnit\Framework\MockObject\MockObject Mocked dependency for token generation */
    private JwtService $jwt;

    /** @var SigninService The service under test */
    private SigninService $service;

    use ApiTestHelperTrait;

    /**
     * Set up mock dependencies and initialize SigninService.
     *
     * Called automatically before each test method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies to isolate SigninService behavior
        $this->userQueries = $this->createMock(UserQueries::class);
        $this->cookieManager = $this->createMock(CookieManager::class);
        $this->jwt = $this->createMock(JwtService::class);

        // Inject mocks into the service
        $this->service = new SigninService(
            $this->userQueries,
            $this->cookieManager,
            $this->jwt
        );
    }

    /**
     * Provides invalid input cases for execute().
     *
     * Covers missing or empty username/password combinations.
     *
     * @return array<string, array{0: array<string,mixed>}>
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
     * Uses the invalidInputProvider for multiple cases.
     *
     * @param array<string,mixed> $input Invalid request body data.
     *
     * @return void
     */
    #[DataProvider('invalidInputProvider')]
    public function testExecuteThrowsInvalidArgumentExceptionForInvalidInput(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Create mock request with invalid input
        /** @var array<string,mixed> $input */
        $req = $this->makeRequest($input);

        // Expect exception due to invalid username/password
        $this->service->execute($req);
    }

    /**
     * Provides failure cases for getUserByName() call.
     *
     * Each case simulates a DB query failure with or without error info.
     *
     * @return array<string, array{0:QueryResult,1:string}>
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
                'Failed to fetch user: Unknown database error.'
            ],
        ];
    }

    /**
     * Test that execute() throws RuntimeException when DB query fails.
     *
     * @param QueryResult $result Simulated DB failure result.
     * @param string      $expectedMessage Expected exception message.
     *
     * @return void
     */
    #[DataProvider('getUserByNameFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenGetUserByNameFails(QueryResult $result, string $expectedMessage): void
    {
        // Simulate query failure
        $this->userQueries->method('getUserByName')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        // Execute with valid request data
        $req = $this->makeRequest(['username' => 'john', 'password' => 'pass']);
        $this->service->execute($req);
    }

    /**
     * Test that execute() throws when no user is found in DB.
     *
     * Simulates a query that succeeds but returns zero rows.
     *
     * @return void
     */
    public function testExecuteThrowsInvalidArgumentExceptionWhenUserNotFound(): void
    {
        $this->userQueries->method('getUserByName')->willReturn(QueryResult::ok(null, 0));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch user: No data or changes found.');

        $req = $this->makeRequest(['username' => 'john', 'password' => 'pass']);
        $this->service->execute($req);
    }

    /**
     * Test that execute() throws for incorrect password.
     *
     * Ensures password verification logic rejects invalid credentials.
     *
     * @return void
     */
    public function testExecuteThrowsInvalidArgumentExceptionForWrongPassword(): void
    {
        // Simulate a user record with known password
        $user = ['id' => 'uuid', 'username' => 'john', 'password' => password_hash('correct-pass', PASSWORD_DEFAULT)];
        $this->userQueries->method('getUserByName')->willReturn(QueryResult::ok($user, 1));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid username or password.');

        // Use wrong password for login attempt
        $req = $this->makeRequest(['username' => 'john', 'password' => 'wrong-pass']);
        $this->service->execute($req);
    }

    /**
     * Test that execute() successfully creates JWT and sets cookie.
     *
     * Verifies that both JwtService::create() and
     * CookieManager::setAccessToken() are called once.
     *
     * @return void
     */
    public function testExecuteCallsJwtAndCookieManagerWhenSuccessful(): void
    {
        // Mock a valid user record
        $user = ['id' => 'uuid', 'username' => 'john', 'password' => password_hash('pass123', PASSWORD_DEFAULT)];
        $this->userQueries->method('getUserByName')->willReturn(QueryResult::ok($user, 1));

        // Expect JWT to be created with correct payload
        $this->jwt->expects($this->once())
            ->method('create')
            ->with(['id' => 'uuid'])
            ->willReturn('mock-token');

        // Expect access token to be stored in cookie
        $this->cookieManager->expects($this->once())
            ->method('setAccessToken')
            ->with('mock-token', $this->anything());

        // Execute sign-in flow with correct credentials
        $req = $this->makeRequest(['username' => 'john', 'password' => 'pass123']);
        $this->service->execute($req);
    }
}
