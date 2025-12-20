<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\BulkMarkDoneTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use Tests\Unit\Api\TestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class BulkMarkDoneTaskServiceUnitTest
 *
 * Unit tests for the BulkMarkDoneTaskService class.
 *
 * @package Tests\Unit\Api\Tasks\Service
 */
class BulkMarkDoneTaskServiceUnitTest extends TestCase
{
    use TestHelperTrait;

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
     * Test successful bulk mark done.
     *
     * @return void
     */
    public function testExecuteSuccessMarkDone(): void
    {
        $ids = [1, 2];
        $userId = 'u1';
        $isDone = true;

        // Expect markTasksDone with isDone = true
        $this->taskQueries->expects($this->once())
            ->method('markTasksDone')
            ->with($ids, true, $userId)
            ->willReturn(QueryResult::ok(null, 2));

        $req = $this->makeRequest([], [], [], 'POST', '/', ['id' => $userId]);
        $req->body = ['ids' => $ids, 'is_done' => true];

        $result = $this->service->execute($req);

        $this->assertEquals(['count' => 2], $result);
    }

    /**
     * Test successful bulk mark undone.
     *
     * @return void
     */
    public function testExecuteSuccessMarkUndone(): void
    {
        $ids = [1, 2];
        $userId = 'u1';
        $isDone = false;

        $this->taskQueries->expects($this->once())
            ->method('markTasksDone')
            ->with($ids, false, $userId)
            ->willReturn(QueryResult::ok(null, 2));

        $req = $this->makeRequest([], [], [], 'POST', '/', ['id' => $userId]);
        $req->body = ['ids' => $ids, 'is_done' => false];

        $result = $this->service->execute($req);

        $this->assertEquals(['count' => 2], $result);
    }

    /**
     * Test empty IDs results in 0 count.
     *
     * @return void
     */
    public function testExecuteEmptyIdsReturnsZero(): void
    {
        $req = $this->makeRequest([], [], [], 'POST', '/', ['id' => 'u1']);
        $req->body = ['ids' => [], 'is_done' => true];

        $this->taskQueries->expects($this->never())->method('markTasksDone');

        $result = $this->service->execute($req);

        $this->assertEquals(['count' => 0], $result);
    }

    /**
     * Test limit enforcement.
     *
     * @return void
     */
    public function testExecuteThrowsOnTooManyIds(): void
    {
        $ids = range(1, 51);
        $req = $this->makeRequest([], [], [], 'POST', '/', ['id' => 'u1']);
        $req->body = ['ids' => $ids, 'is_done' => true];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot update more than 50 tasks');

        $this->service->execute($req);
    }

    /**
     * Test database failure handling.
     *
     * @return void
     */
    public function testExecuteThrowsOnDbFailure(): void
    {
        $ids = [1];
        $userId = 'u1';

        $this->taskQueries->method('markTasksDone')
            ->willReturn(QueryResult::fail(['DB Error']));

        $req = $this->makeRequest([], [], [], 'POST', '/', ['id' => $userId]);
        $req->body = ['ids' => $ids, 'is_done' => true];

        $this->expectException(RuntimeException::class);
        $this->service->execute($req);
    }
}
