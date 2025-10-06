<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use App\Api\Auth\Service\GetUserService;
use App\Api\Request;
use App\DB\UserQueries;
use App\DB\QueryResult;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;

class GetUserServiceTypeErrTest extends TestCase
{
    private GetUserService $service;
    private UserQueries $userQueries;

    protected function setUp(): void
    {
        $this->userQueries = $this->createMock(UserQueries::class);
        $this->service = new GetUserService($this->userQueries);
    }

    public static function invalidExecuteArgsProvider(): array
    {
        return [
            'null instead of Request'   => [null],
            'int instead of Request'    => [123],
            'array instead of Request'  => [[]],
            'string instead of Request' => ['not-a-request'],
        ];
    }

    #[DataProvider('invalidExecuteArgsProvider')]
    public function testExecuteThrowsTypeErrorWhenNotRequest(mixed $invalidArg): void
    {
        $this->expectException(TypeError::class);
        /** @phpstan-ignore-next-line deliberately wrong type */
        $this->service->execute($invalidArg);
    }

    public static function invalidUserIdProvider(): array
    {
        return [
            'user_id missing' => [
                fn() => new Request()
            ],
            'user_id null' => [
                function () {
                    $req = new Request();
                    $req->params['user_id'] = null;
                    return $req;
                }
            ],
            'user_id empty string' => [
                function () {
                    $req = new Request();
                    $req->params['user_id'] = '';
                    return $req;
                }
            ],
        ];
    }

    #[DataProvider('invalidUserIdProvider')]
    public function testExecuteThrowsInvalidArgumentExceptionWhenUserIdInvalid(callable $requestFactory): void
    {
        $req = $requestFactory();
        $this->expectException(InvalidArgumentException::class);
        $this->service->execute($req);
    }

    public function testExecuteThrowsRuntimeExceptionWhenEnsureSuccessFails(): void
    {
        $req = new Request();
        $req->params['user_id'] = '123';

        $result = QueryResult::ok(['username' => 'u', 'email' => 'e'], 1);
        $result->success = false; // force fail

        $this->userQueries
            /** @phpstan-ignore-next-line */
            ->method('getUserById')
            ->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch user');

        $this->service->execute($req);
    }

    public function testExecuteThrowsRuntimeExceptionWhenUserNotFound(): void
    {
        $req = new Request();
        $req->params['user_id'] = '123';

        $result = QueryResult::ok(null, 0);

        $this->userQueries
            /** @phpstan-ignore-next-line */
            ->method('getUserById')
            ->willReturn($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch user: No changes were made.');

        $this->service->execute($req);
    }

    public function testExecuteReturnsUserArrayOnSuccess(): void
    {
        $req = new Request();
        $req->params['user_id'] = '123';

        $result = QueryResult::ok(
            ['username' => 'john', 'email' => 'john@example.com'],
            1
        );

        $this->userQueries
            /** @phpstan-ignore-next-line */
            ->method('getUserById')
            ->willReturn($result);

        $output = $this->service->execute($req);

        $this->assertSame(
            ['username' => 'john', 'email' => 'john@example.com'],
            $output
        );
    }
}
