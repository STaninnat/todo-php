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

/**
 * Class SigninServiceTypeErrTest
 *
 * Unit tests for SigninService that specifically verify
 * TypeError handling during construction and execution.
 *
 * These tests ensure strict type safety for constructor dependencies
 * and the execute() method parameter.
 *
 * @package Tests\Unit\Api\Auth\Service\TypeError
 */
class SigninServiceTypeErrTest extends TestCase
{
    /**
     * Test that constructor throws TypeError when UserQueries dependency is invalid.
     *
     * Ensures the first constructor argument must be an instance of UserQueries.
     *
     * @return void
     */
    public function testConstructorThrowsTypeErrorWhenUserQueriesIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        // Pass invalid string instead of UserQueries mock
        new SigninService(
            "notUserQueries",
            $this->createMock(CookieManager::class),
            $this->createMock(JwtService::class)
        );
    }

    /**
     * Test that constructor throws TypeError when CookieManager dependency is invalid.
     *
     * Ensures the second constructor argument must be an instance of CookieManager.
     *
     * @return void
     */
    public function testConstructorThrowsTypeErrorWhenCookieManagerIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        // Pass invalid string instead of CookieManager mock
        new SigninService(
            $this->createMock(UserQueries::class),
            "notCookieManager",
            $this->createMock(JwtService::class)
        );
    }

    /**
     * Test that constructor throws TypeError when JwtService dependency is invalid.
     *
     * Ensures the third constructor argument must be an instance of JwtService.
     *
     * @return void
     */
    public function testConstructorThrowsTypeErrorWhenJwtServiceIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        // Pass invalid string instead of JwtService mock
        new SigninService(
            $this->createMock(UserQueries::class),
            $this->createMock(CookieManager::class),
            "notJwtService"
        );
    }

    /**
     * Test that execute() throws TypeError when provided argument is not a Request.
     *
     * Verifies strict type enforcement in the execute() method.
     *
     * @param mixed $invalidReq Invalid value to simulate incorrect parameter type.
     *
     * @return void
     */
    #[DataProvider('invalidRequestProvider')]
    public function testExecuteThrowsTypeErrorWhenRequestIsInvalid(mixed $invalidReq): void
    {
        // Create service with valid dependencies
        $service = new SigninService(
            $this->createMock(UserQueries::class),
            $this->createMock(CookieManager::class),
            $this->createMock(JwtService::class)
        );

        $this->expectException(TypeError::class);

        // Call execute() with invalid type
        $service->execute($invalidReq);
    }

    /**
     * Provides invalid input types for execute() parameter.
     *
     * Covers common primitive and null values that should
     * trigger TypeError when passed instead of a Request instance.
     *
     * @return array<string,array{0:mixed}>
     */
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
