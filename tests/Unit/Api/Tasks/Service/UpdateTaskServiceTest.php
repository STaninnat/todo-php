<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\UpdateTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

class UpdateTaskServiceTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    private UpdateTaskService $service;

    protected function setUp(): void
    {
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new UpdateTaskService($this->taskQueries);
    }

    use ApiTestHelperTrait;

    public function testMissingIdOrUserIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest(['user_id' => '123']);
        $this->service->execute($req);
    }

    public function testMissingTitleThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest(['id' => '1', 'user_id' => '123']);
        $this->service->execute($req);
    }

    public function testInvalidStatusValueDefaultsToZero(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'description' => '', 'is_done' => 0];

        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('updateTask')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('getTotalTasks')
            ->willReturn(QueryResult::ok(12));

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'title' => 'Task',
            'description' => '',
            'is_done' => 2 // invalid -> defaults to 0
        ]);

        $result = $this->service->execute($req);

        $this->assertSame($task, $result['task']);
        $this->assertSame(2, $result['totalPages']);
    }

    public function testNonNumericTaskIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => 'abc',
            'user_id' => '123',
            'title' => 'Task'
        ]);

        $this->service->execute($req);
    }

    public function testGetTaskByIdFailsThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::fail(['DB error']));

        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'title' => 'Task'
        ]);

        $this->service->execute($req);
    }

    public function testGetTaskByIdReturnsNullThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok(null, 0));

        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'title' => 'Task'
        ]);

        $this->service->execute($req);
    }

    public function testUpdateTaskFailsThrowsRuntimeException(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'description' => '', 'is_done' => 0];

        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('updateTask')
            ->willReturn(QueryResult::fail(['Update error']));

        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'title' => 'Task'
        ]);

        $this->service->execute($req);
    }

    public function testUpdateTaskNotChangedThrowsRuntimeException(): void
    {
        $task = ['id' => 1, 'title' => 'Task', 'description' => '', 'is_done' => 0];

        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('updateTask')
            ->willReturn(QueryResult::ok($task, 0));

        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'title' => 'Task'
        ]);

        $this->service->execute($req);
    }

    public function testUpdateTaskSuccessReturnsExpectedArray(): void
    {
        $task = ['id' => 1, 'title' => 'Updated Task', 'description' => 'Desc', 'is_done' => 1];

        $this->taskQueries->method('getTaskByID')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('updateTask')
            ->willReturn(QueryResult::ok($task, 1));

        $this->taskQueries->method('getTotalTasks')
            ->willReturn(QueryResult::ok(25));

        $req = $this->makeRequest([
            'id' => '1',
            'user_id' => '123',
            'title' => 'Updated Task',
            'description' => 'Desc',
            'is_done' => 1
        ]);

        $result = $this->service->execute($req);

        $this->assertSame($task, $result['task']);
        $this->assertSame(3, $result['totalPages']);
    }
}
