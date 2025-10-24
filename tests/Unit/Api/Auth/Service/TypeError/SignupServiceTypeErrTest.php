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

/**
 * Class SignupServiceTypeErrTest
 *
 * Unit tests for type safety in the SignupService class.
 *
 * These tests ensure that SignupService constructor and execute()
 * correctly throw TypeError when given invalid argument types.
 *
 * This helps verify strict typing compliance and prevent
 * misuse of dependencies or input parameters.
 *
 * @package Tests\Unit\Api\Auth\Service\TypeError
 */
class SignupServiceTypeErrTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject&UserQueries Mocked UserQueries instance */
    private $userQueriesMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject&CookieManager Mocked CookieManager instance */
    private $cookieManagerMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject&JwtService Mocked JwtService instance */
    private $jwtMock;

    /**
     * Setup mock dependencies before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects for dependencies
        $this->userQueriesMock = $this->createMock(UserQueries::class);
        $this->cookieManagerMock = $this->createMock(CookieManager::class);
        $this->jwtMock = $this->createMock(JwtService::class);
    }

    /**
     * Test that constructor throws TypeError when UserQueries argument is invalid.
     *
     * @return void
     */
    public function testConstructorThrowsTypeErrorWhenUserQueriesIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        // Pass a string instead of UserQueries instance
        new SignupService("notUserQueries", $this->cookieManagerMock, $this->jwtMock);
    }

    /**
     * Test that constructor throws TypeError when CookieManager argument is invalid.
     *
     * @return void
     */
    public function testConstructorThrowsTypeErrorWhenCookieManagerIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        // Pass an integer instead of CookieManager instance
        new SignupService($this->userQueriesMock, 123, $this->jwtMock);
    }

    /**
     * Test that constructor throws TypeError when JwtService argument is invalid.
     *
     * @return void
     */
    public function testConstructorThrowsTypeErrorWhenJwtServiceIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        // Pass an array instead of JwtService instance
        new SignupService($this->userQueriesMock, $this->cookieManagerMock, []);
    }

    /**
     * Test that execute() throws TypeError when called with an invalid Request.
     *
     * @return void
     */
    public function testExecuteThrowsTypeErrorWhenRequestIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        // Properly constructed service with mocks
        $service = new SignupService(
            $this->userQueriesMock,
            $this->cookieManagerMock,
            $this->jwtMock
        );

        // Pass a string instead of Request instance — should cause TypeError
        $service->execute("notARequest");
    }
}
