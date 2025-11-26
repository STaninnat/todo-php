<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use App\Api\Auth\Service\SignoutService;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Class SignoutServiceTypeErrTest
 *
 * Unit tests for SignoutService focusing on TypeError handling.
 *
 * Ensures that the constructor enforces strict type validation
 * for its dependency (CookieManager).
 *
 * @package Tests\Unit\Api\Auth\Service\TypeError
 */
class SignoutServiceTypeErrTest extends TestCase
{
    /**
     * Test that constructor throws TypeError when CookieManager dependency is invalid.
     *
     * Verifies that the SignoutService constructor strictly requires
     * a valid CookieManager instance and rejects incorrect types.
     *
     * @return void
     */
    public function testConstructorThrowsTypeErrorWhenCookieManagerIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        // Attempt to construct service with invalid type (string instead of CookieManager)
        new SignoutService("notCookieManager");
    }
}
