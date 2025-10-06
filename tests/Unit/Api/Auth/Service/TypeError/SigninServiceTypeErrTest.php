<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use App\Api\Auth\Service\SigninService;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypeError;

class SigninServiceTypeErrTest extends TestCase
{
    public function testConstructorThrowsTypeErrorWhenUserQueriesIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        new SigninService("notUserQueries", $this->createMock(CookieManager::class), $this->createMock(JwtService::class));
    }

    public function testConstructorThrowsTypeErrorWhenCookieManagerIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        new SigninService($this->createMock(UserQueries::class), "notCookieManager", $this->createMock(JwtService::class));
    }

    public function testConstructorThrowsTypeErrorWhenJwtServiceIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        new SigninService($this->createMock(UserQueries::class), $this->createMock(CookieManager::class), "notJwtService");
    }

    #[DataProvider('invalidRequestProvider')]
    public function testExecuteThrowsTypeErrorWhenRequestIsInvalid(mixed $invalidReq): void
    {
        $service = new SigninService(
            $this->createMock(UserQueries::class),
            $this->createMock(CookieManager::class),
            $this->createMock(JwtService::class)
        );

        $this->expectException(TypeError::class);

        $service->execute($invalidReq);
    }

    public static function invalidRequestProvider(): array
    {
        return [
            'string instead of Request' => ["notARequest"],
            'array instead of Request'  => [["username" => "foo", "password" => "bar"]],
            'int instead of Request'    => [123],
            'float instead of Request'  => [45.67],
            'bool instead of Request'   => [true],
            'null instead of Request'   => [null],
        ];
    }
}
