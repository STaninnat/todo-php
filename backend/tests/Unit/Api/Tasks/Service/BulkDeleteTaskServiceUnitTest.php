<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\BulkDeleteTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use Tests\Unit\Api\TestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class BulkDeleteTaskServiceUnitTest
 *
 * Unit tests for the BulkDeleteTaskService class.
 *
 * Covers:
 * - Successful deletion
 * - Input validation (array of IDs, numeric checks)
 * - Limit enforcement (max 50)
 * - Error handling from database
 *
 * @package Tests\Unit\Api\Tasks\Service
 */
class BulkDeleteTaskServiceUnitTest extends TestCase
{
    use TestHelperTrait;

    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    /** @var BulkDeleteTaskService */
    private BulkDeleteTaskService $service;

    protected function setUp(): void
    {
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new BulkDeleteTaskService($this->taskQueries);
    }

    /**
     * Test successful bulk delete.
     * 
     * @return void
     */
    public function testExecuteSuccess(): void
    {
        $ids = [1, 2, 3];
        $userId = 'u1';

        $this->taskQueries->expects($this->once())
            ->method('deleteTasks')
            ->with($ids, $userId)
            ->willReturn(QueryResult::ok(null, 3));

        $req = $this->makeRequest([], [], [], 'POST', '/tasks/bulk-delete', ['id' => $userId]);

        $req = $this->makeRequest(['ids' => $ids], [], [], 'POST', '/', ['id' => $userId]);

        $req->body = ['ids' => $ids];

        $result = $this->service->execute($req);

        $this->assertEquals(['count' => 3], $result);
    }

    /**
     * Test execution with empty IDs returns count 0.
     * 
     * @return void
     */
    public function testExecuteEmptyIds(): void
    {
        $req = $this->makeRequest(['ids' => []], [], [], 'POST', '/', ['id' => 'u1']);
        $req->body = ['ids' => []];

        // taskQueries->deleteTasks should NOT be called
        $this->taskQueries->expects($this->never())->method('deleteTasks');

        $result = $this->service->execute($req);

        $this->assertEquals(['count' => 0], $result);
    }

    /**
     * Test filtering of non-numeric IDs.
     * 
     * @return void
     */
    public function testExecuteFiltersNonNumeric(): void
    {
        $ids = [1, 'invalid', 2];
        $expectedIds = [1, 2];
        $userId = 'u1';

        $this->taskQueries->expects($this->once())
            ->method('deleteTasks')
            // PHPUnit constraint for checking array subset/equality
            ->with(
                $this->callback(function ($arg) use ($expectedIds) {
                    // Check if values match, ignoring keys if array_filter preserved them
                    return array_values((array) $arg) === $expectedIds;
                }),
                $userId
            )
            ->willReturn(QueryResult::ok(null, 2));

        $req = $this->makeRequest(['ids' => $ids], [], [], 'POST', '/', ['id' => $userId]);
        $req->body = ['ids' => $ids];

        $result = $this->service->execute($req);

        $this->assertEquals(['count' => 2], $result);
    }

    /**
     * Test limit enforcement (max 50).
     * 
     * @return void
     */
    public function testExecuteThrowsOnTooManyIds(): void
    {
        $ids = range(1, 51); // 51 items
        $req = $this->makeRequest(['ids' => $ids], [], [], 'POST', '/', ['id' => 'u1']);
        $req->body = ['ids' => $ids];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete more than 50 tasks');

        $this->service->execute($req);
    }

    /**
     * Test database failure throws RuntimeException.
     * 
     * @return void
     */
    public function testExecuteThrowsOnDbFailure(): void
    {
        $ids = [1];
        $userId = 'u1';

        $this->taskQueries->method('deleteTasks')
            ->willReturn(QueryResult::fail(['DB error']));

        $req = $this->makeRequest(['ids' => $ids], [], [], 'POST', '/', ['id' => $userId]);
        $req->body = ['ids' => $ids];

        $this->expectException(RuntimeException::class);
        $this->service->execute($req);
    }
}
