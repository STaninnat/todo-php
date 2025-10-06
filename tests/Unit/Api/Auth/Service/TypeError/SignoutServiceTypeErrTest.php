<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use App\Api\Auth\Service\SignoutService;
use PHPUnit\Framework\TestCase;
use TypeError;

class SignoutServiceTypeErrTest extends TestCase
{
    public function testConstructorThrowsTypeErrorWhenCookieManagerIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        new SignoutService("notCookieManager");
    }
}
