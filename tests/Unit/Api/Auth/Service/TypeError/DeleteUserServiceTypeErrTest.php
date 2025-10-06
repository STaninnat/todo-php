<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use App\Api\Auth\Service\DeleteUserService;
use App\Api\Request;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypeError;

class DeleteUserServiceTypeErrTest extends TestCase
{
    private DeleteUserService $service;
    private UserQueries $userQueries;
    private CookieManager $cookieManager;

    protected function setUp(): void
    {
        $this->userQueries = $this->createMock(UserQueries::class);
        $this->cookieManager = $this->createMock(CookieManager::class);

        $this->service = new DeleteUserService(
            $this->userQueries,
            $this->cookieManager
        );
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
}
