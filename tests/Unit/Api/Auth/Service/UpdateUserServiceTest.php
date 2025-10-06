<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Auth\Service\UpdateUserService;
use App\DB\QueryResult;
use App\DB\UserQueries;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

class UpdateUserServiceTest extends TestCase
{
    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $userQueries;

    private UpdateUserService $service;

    use ApiTestHelperTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userQueries = $this->createMock(UserQueries::class);
        $this->service = new UpdateUserService($this->userQueries);
    }

    public static function invalidInputProvider(): array
    {
        return [
            'missing user_id'  => [[]],
            'empty user_id'    => [['user_id' => '']],
            'whitespace id'    => [['user_id' => '   ']],
            'missing username' => [['user_id' => '1']],
            'empty username'   => [['user_id' => '1', 'username' => '']],
            'missing email'    => [['user_id' => '1', 'username' => 'john']],
            'empty email'      => [['user_id' => '1', 'username' => 'john', 'email' => '']],
            'invalid email'    => [['user_id' => '1', 'username' => 'john', 'email' => 'not-an-email']],
        ];
    }

    #[DataProvider('invalidInputProvider')]
    public function testExecuteThrowsInvalidArgumentException(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest($input);
        $this->service->execute($req);
    }

    public static function checkUserExistsFailProvider(): array
    {
        return [
            'with error info' => [
                QueryResult::fail(['SQLSTATE[HY000]', 'Some error']),
                'Failed to check user existence: SQLSTATE[HY000] | Some error'
            ],
            'without error'   => [
                QueryResult::fail(null),
                'Failed to check user existence: No changes were made.'
            ],
        ];
    }

    #[DataProvider('checkUserExistsFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenCheckUserExistsFails(QueryResult $result, string $expectedMessage): void
    {
        $this->userQueries->method('checkUserExists')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $req = $this->makeRequest(['user_id' => '1', 'username' => 'john', 'email' => 'john@example.com']);
        $this->service->execute($req);
    }

    public function testExecuteThrowsRuntimeExceptionWhenUsernameOrEmailAlreadyExists(): void
    {
        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(true, 1));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Username or email already exists.');

        $req = $this->makeRequest(['user_id' => '1', 'username' => 'john', 'email' => 'john@example.com']);
        $this->service->execute($req);
    }

    public static function updateUserFailProvider(): array
    {
        return [
            'fail with error info' => [
                QueryResult::fail(['SQLSTATE[HY000]', 'Update error']),
                'Failed to check user existence: No changes were made.'
            ],
            'fail without error' => [
                QueryResult::fail(null),
                'Failed to check user existence: No changes were made.'
            ],
        ];
    }

    #[DataProvider('updateUserFailProvider')]
    public function testExecuteThrowsRuntimeExceptionWhenUpdateUserFails(QueryResult $result, string $expectedMessage): void
    {
        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 0));
        $this->userQueries->method('updateUser')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $req = $this->makeRequest(['user_id' => '1', 'username' => 'john', 'email' => 'john@example.com']);
        $this->service->execute($req);
    }

    public function testExecuteThrowsRuntimeExceptionWhenUpdateUserHasNoChanges(): void
    {
        $userData = ['username' => 'john', 'email' => 'john@example.com'];
        $result = QueryResult::ok($userData, 0);

        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 0));
        $this->userQueries->method('updateUser')->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to check user existence: No changes were made.');

        $req = $this->makeRequest(['user_id' => '1', 'username' => 'john', 'email' => 'john@example.com']);
        $this->service->execute($req);
    }

    public function testExecuteReturnsUpdatedUserDataWhenSuccessful(): void
    {
        $userData = ['username' => 'john', 'email' => 'john@example.com'];
        $result = QueryResult::ok($userData, 1);

        $this->userQueries->method('checkUserExists')->willReturn(QueryResult::ok(false, 1));
        $this->userQueries->method('updateUser')->willReturn($result);

        $req = $this->makeRequest(['user_id' => '1', 'username' => 'john', 'email' => 'john@example.com']);
        $output = $this->service->execute($req);

        $this->assertSame($userData, $output);
    }
}
