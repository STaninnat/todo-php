<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use App\Api\Auth\Service\UpdateUserService;
use App\Api\Request;
use App\DB\UserQueries;
use PHPUnit\Framework\TestCase;
use TypeError;

class UpdateUserServiceTypeErrTest extends TestCase
{
    private $userQueriesMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userQueriesMock = $this->createMock(UserQueries::class);
    }

    public function testConstructorThrowsTypeErrorWhenUserQueriesIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        new UpdateUserService("notUserQueries");
    }

    public function testExecuteThrowsTypeErrorWhenRequestIsInvalid(): void
    {
        $this->expectException(TypeError::class);

        $service = new UpdateUserService($this->userQueriesMock);

        $service->execute(123); // ไม่ใช่ Request
    }
}
