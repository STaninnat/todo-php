<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use App\Api\Auth\Service\SignupService;
use App\Api\Request;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use PHPUnit\Framework\TestCase;
use TypeError;

class SignupServiceTypeErrTest extends TestCase
{
    private $userQueriesMock;
    private $cookieManagerMock;
    private $jwtMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userQueriesMock = $this->createMock(UserQueries::class);
        $this->cookieManagerMock = $this->createMock(CookieManager::class);
        $this->jwtMock = $this->createMock(JwtService::class);
    }

    public function testConstructorThrowsTypeErrorWhenUserQueriesIsInvalid(): void
    {
        $this->expectException(TypeError::class);
        new SignupService("notUserQueries", $this->cookieManagerMock, $this->jwtMock);
    }

    public function testConstructorThrowsTypeErrorWhenCookieManagerIsInvalid(): void
    {
        $this->expectException(TypeError::class);
        new SignupService($this->userQueriesMock, 123, $this->jwtMock);
    }

    public function testConstructorThrowsTypeErrorWhenJwtServiceIsInvalid(): void
    {
        $this->expectException(TypeError::class);
        new SignupService($this->userQueriesMock, $this->cookieManagerMock, []);
    }

    public function testExecuteThrowsTypeErrorWhenRequestIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        $service = new SignupService(
            $this->userQueriesMock,
            $this->cookieManagerMock,
            $this->jwtMock
        );

        $service->execute("notARequest");
    }
}
