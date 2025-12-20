<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service\TypeError;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\BulkDeleteTaskService;
use App\DB\TaskQueries;
use App\Api\Request;
use InvalidArgumentException;
use TypeError;

/**
 * Class BulkDeleteTaskServiceTypeErrTest
 *
 * Unit tests for BulkDeleteTaskService type errors.
 *
 * @package Tests\Unit\Api\Tasks\Service\TypeError
 */
class BulkDeleteTaskServiceTypeErrTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    /** @var BulkDeleteTaskService Service instance under test */
    private BulkDeleteTaskService $service;

    /**
     * Setup mocks and service.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new BulkDeleteTaskService($this->taskQueries);
    }

    /**
     * Test execute throws InvalidArgumentException (via RequestValidator) when ids is not an array.
     * 
     * @return void
     */
    public function testExecuteThrowsOnInvalidIdsType(): void
    {
        $req = new Request('POST', '/');
        $req->auth = ['id' => 'u1'];
        $req->body = ['ids' => 'not-an-array'];

        $this->expectException(InvalidArgumentException::class);
        $this->service->execute($req);
    }

    /**
     * Test execute throws when user id is missing (RequestValidator throws RuntimeException).
     * 
     * @return void
     */
    public function testExecuteThrowsOnMissingAuth(): void
    {
        $req = new Request('POST', '/');
        $req->body = ['ids' => [1]];
        // No auth set

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Authenticated user ID not found');
        $this->service->execute($req);
    }
}
