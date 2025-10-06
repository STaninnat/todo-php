<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\MarkDoneTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;
use Error;

class MarkDoneTaskServiceTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    private MarkDoneTaskService $service;

    protected function setUp(): void
    {
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new MarkDoneTaskService($this->taskQueries);
    }

    use ApiTestHelperTrait;

    public function testMissingIdOrUserIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest(['user_id' => '123']);
        $this->service->execute($req);
    }

    public function testInvalidStatusValueThrowsException(): void
    {
        $this->expectException(Error::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => 2
        ]);
        $this->service->execute($req);
    }

    public function testNonNumericTaskIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => 'abc',
            'user_id' => '123',
            'is_done' => 1
        ]);
        $this->service->execute($req);
    }

    public function testGetTaskByIdFailsThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::fail(['DB error']));

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => 1
        ]);
        $this->service->execute($req);
    }

    public function testGetTaskByIdReturnsNullThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok(null, 0));

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => 1
        ]);
        $this->service->execute($req);
    }

    public function testMarkDoneFailsThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok(['id' => 1, 'title' => 'Task'], 1));

        $this->taskQueries->method('markDone')
            ->willReturn(QueryResult::fail(['Update error']));

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => 1
        ]);
        $this->service->execute($req);
    }

    public function testMarkDoneNotChangedThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok(['id' => 1, 'title' => 'Task'], 1));

        $this->taskQueries->method('markDone')
            ->willReturn(QueryResult::ok(['id' => 1, 'title' => 'Task'], 0));

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => 1
        ]);
        $this->service->execute($req);
    }

    public function testMarkDoneSuccessReturnsExpectedArray(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'is_done' => 1];

        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('markDone')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('getTotalTasks')
            ->willReturn(QueryResult::ok(15));

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => 1
        ]);

        $result = $this->service->execute($req);

        $this->assertIsArray($result);
        $this->assertSame($task, $result['task']);
        $this->assertSame(2, $result['totalPages']); // ceil(15 / 10)
    }

    public function testIsDoneAcceptsStringZeroOrOne(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'is_done' => 1];

        $this->taskQueries->method('getTaskByID')
            ->willReturn(\App\DB\QueryResult::ok($task, 1));

        $this->taskQueries->method('markDone')
            ->willReturn(\App\DB\QueryResult::ok($task, 1));

        $this->taskQueries->method('getTotalTasks')
            ->willReturn(\App\DB\QueryResult::ok(5));

        // --- case "0"
        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => "0"
        ]);
        $result = $this->service->execute($req);
        $this->assertIsArray($result);
        $this->assertSame($task, $result['task']);

        // --- case "1"
        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => "1"
        ]);
        $result = $this->service->execute($req);
        $this->assertIsArray($result);
        $this->assertSame($task, $result['task']);
    }

    public function testIsDoneInvalidStringThrowsException(): void
    {
        $this->expectException(Error::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'is_done' => "abc"
        ]);
        $this->service->execute($req);
    }

    public function testIsDoneMissingThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123'
        ]);
        $this->service->execute($req);
    }
}
