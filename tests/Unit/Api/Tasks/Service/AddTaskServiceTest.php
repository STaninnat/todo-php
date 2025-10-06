<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\AddTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use Error;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

class AddTaskServiceTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    private AddTaskService $service;

    protected function setUp(): void
    {
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new AddTaskService($this->taskQueries);
    }

    use ApiTestHelperTrait;

    public function testThrowsIfTitleMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest(['description' => 'desc', 'user_id' => '1']);
        $this->service->execute($req);
    }

    public function testThrowsIfUserIdMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest(['title' => 'My Task', 'description' => 'desc']);
        $this->service->execute($req);
    }

    public function testThrowsIfAddTaskFails(): void
    {
        $this->taskQueries
            ->expects($this->once())
            ->method('addTask')
            ->willReturn(QueryResult::fail(['db error']));

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest([
            'title' => 'My Task',
            'description' => 'desc',
            'user_id' => '1',
        ]);
        $this->service->execute($req);
    }

    public function testThrowsIfNoRowsChanged(): void
    {
        $result = QueryResult::ok(['id' => 1], 0);

        $this->taskQueries
            ->expects($this->once())
            ->method('addTask')
            ->willReturn($result);

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest([
            'title' => 'My Task',
            'description' => 'desc',
            'user_id' => '1',
        ]);
        $this->service->execute($req);
    }

    public function testThrowsIfNoDataReturned(): void
    {
        $result = QueryResult::ok([], 1);

        $this->taskQueries
            ->expects($this->once())
            ->method('addTask')
            ->willReturn($result);

        $this->expectException(Error::class);

        $req = $this->makeRequest([
            'title' => 'My Task',
            'description' => 'desc',
            'user_id' => '1',
        ]);
        $this->service->execute($req);
    }

    public function testReturnsTaskAndTotalPagesOnSuccess(): void
    {
        $taskData = ['id' => 1, 'title' => 'My Task', 'user_id' => 1];
        $result = QueryResult::ok($taskData, 1);

        $this->taskQueries
            ->expects($this->once())
            ->method('addTask')
            ->willReturn($result);

        $this->taskQueries
            ->expects($this->once())
            ->method('getTotalTasks')
            ->willReturn(QueryResult::ok(25));

        $req = $this->makeRequest([
            'title' => 'My Task',
            'description' => 'desc',
            'user_id' => '1',
        ]);

        $output = $this->service->execute($req);

        $this->assertEquals($taskData, $output['task']);
        $this->assertEquals(3, $output['totalPages']);
    }
}
