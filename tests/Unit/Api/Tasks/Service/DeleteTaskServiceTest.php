<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\DeleteTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

class DeleteTaskServiceTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    private DeleteTaskService $service;

    protected function setUp(): void
    {
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new DeleteTaskService($this->taskQueries);
    }

    use ApiTestHelperTrait;

    public function testMissingTaskIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest(['user_id' => '123']);
        $this->service->execute($req);
    }

    public function testMissingUserIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest(['id' => '10']);
        $this->service->execute($req);
    }

    public function testNonNumericTaskIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest(['id' => 'abc', 'user_id' => '123']);
        $this->service->execute($req);
    }

    public function testDeleteFailsReturnsRuntimeException(): void
    {
        $this->taskQueries
            ->expects($this->once())
            ->method('deleteTask')
            ->willReturn(QueryResult::fail(['SQL error']));

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(['id' => '1', 'user_id' => '123']);
        $this->service->execute($req);
    }

    public function testDeleteNotChangedThrowsRuntimeException(): void
    {
        $this->taskQueries
            ->expects($this->once())
            ->method('deleteTask')
            ->willReturn(QueryResult::ok(null, 0));

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(['id' => '1', 'user_id' => '123']);
        $this->service->execute($req);
    }

    public function testDeleteTaskSuccessReturnsExpectedArray(): void
    {
        $this->taskQueries
            ->expects($this->once())
            ->method('deleteTask')
            ->willReturn(QueryResult::ok(null, 1));

        $this->taskQueries
            ->expects($this->once())
            ->method('getTotalTasks')
            ->willReturn(QueryResult::ok(25));

        $req = $this->makeRequest(['id' => '1', 'user_id' => '123']);
        $result = $this->service->execute($req);

        $this->assertIsArray($result);
        $this->assertSame(1, $result['id']);
        $this->assertSame(3, $result['totalPages']);
    }
}
