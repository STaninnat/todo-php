<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use App\Api\Auth\Service\UpdateUserService;
use App\Api\Request;
use App\DB\UserQueries;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Class UpdateUserServiceTypeErrTest
 *
 * Unit tests for UpdateUserService focused on TypeError handling.
 *
 * Ensures that the service constructor and execute() method
 * strictly enforce type hints and throw TypeError when invalid
 * argument types are passed.
 *
 * @package Tests\Unit\Api\Auth\Service\TypeError
 */
class UpdateUserServiceTypeErrTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject&UserQueries Mocked UserQueries dependency */
    private $userQueriesMock;

    /**
     * Sets up mock dependencies before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->userQueriesMock = $this->createMock(UserQueries::class);
    }

    /**
     * Test that the constructor throws TypeError when given an invalid UserQueries dependency.
     *
     * @return void
     */
    public function testConstructorThrowsTypeErrorWhenUserQueriesIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        // Passing string instead of UserQueries instance should trigger TypeError
        new UpdateUserService("notUserQueries");
    }

    /**
     * Test that execute() throws TypeError when given a non-Request argument.
     *
     * @return void
     */
    public function testExecuteThrowsTypeErrorWhenRequestIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        $service = new UpdateUserService($this->userQueriesMock);

        // Passing integer instead of Request object — should raise TypeError
        $service->execute(123);
    }
}
