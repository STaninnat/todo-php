<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\GetTasksService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

class GetTasksServiceTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    private GetTasksService $service;

    protected function setUp(): void
    {
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new GetTasksService($this->taskQueries);
    }

    use ApiTestHelperTrait;

    public function testMissingUserIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest(); // ไม่มี user_id
        $this->service->execute($req);
    }

    public function testGetTasksFailsThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTasksByUserID')
            ->willReturn(QueryResult::fail(['DB error']));

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(['user_id' => '123']);
        $this->service->execute($req);
    }

    public function testGetTasksReturnsNoDataThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTasksByUserID')
            ->willReturn(QueryResult::ok([]));

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(['user_id' => '123']);
        $this->service->execute($req);
    }

    public function testGetTasksSuccessReturnsExpectedArray(): void
    {
        $tasks = [
            ['id' => 1, 'title' => 'Test Task'],
            ['id' => 2, 'title' => 'Another Task'],
        ];

        $this->taskQueries->method('getTasksByUserID')
            ->willReturn(QueryResult::ok($tasks, count($tasks)));

        $this->taskQueries->method('getTotalTasks')
            ->willReturn(QueryResult::ok(20));

        $req = $this->makeRequest(['user_id' => '123']);
        $result = $this->service->execute($req);

        $this->assertIsArray($result);
        $this->assertSame($tasks, $result['task']);
        $this->assertCount(2, $result['task']);
        $this->assertSame(2, $result['totalPages']); // ceil(20/10)
    }
}
