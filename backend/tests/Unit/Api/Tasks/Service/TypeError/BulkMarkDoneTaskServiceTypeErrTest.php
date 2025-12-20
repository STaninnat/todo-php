<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service\TypeError;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\BulkMarkDoneTaskService;
use App\DB\TaskQueries;
use App\Api\Request;
use InvalidArgumentException;
use TypeError;

/**
 * Class BulkMarkDoneTaskServiceTypeErrTest
 *
 * Unit tests for BulkMarkDoneTaskService type errors.
 *
 * @package Tests\Unit\Api\Tasks\Service\TypeError
 */
class BulkMarkDoneTaskServiceTypeErrTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    /** @var BulkMarkDoneTaskService Service instance under test */
    private BulkMarkDoneTaskService $service;

    /**
     * Setup mocks and service.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new BulkMarkDoneTaskService($this->taskQueries);
    }

    /**
     * Test execute throws InvalidArgumentException when ids is not an array.
     *
     * @return void
     */
    public function testExecuteThrowsOnInvalidIdsType(): void
    {
        $req = new Request('POST', '/');
        $req->auth = ['id' => 'u1'];
        $req->body = ['ids' => 'not-array', 'is_done' => true];

        $this->expectException(InvalidArgumentException::class);
        $this->service->execute($req);
    }

    /**
     * Test execute throws InvalidArgumentException when is_done is invalid.
     *
     * @return void
     */
    public function testExecuteThrowsOnInvalidIsDoneType(): void
    {
        $req = new Request('POST', '/');
        $req->auth = ['id' => 'u1'];
        $req->body = ['ids' => [1], 'is_done' => 'not-a-bool'];

        $this->expectException(InvalidArgumentException::class);
        $this->service->execute($req);
    }

    /**
     * Test execute throws when user id is missing.
     *
     * @return void
     */
    public function testExecuteThrowsOnMissingAuth(): void
    {
        $req = new Request('POST', '/');
        $req->body = ['ids' => [1], 'is_done' => true];
        // No auth

        $this->expectException(\RuntimeException::class);
        $this->service->execute($req);
    }
}
