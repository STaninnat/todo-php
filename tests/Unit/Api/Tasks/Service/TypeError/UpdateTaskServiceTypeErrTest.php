<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service\TypeError;

use App\Api\Request;
use App\Api\Tasks\Service\UpdateTaskService;
use App\DB\TaskQueries;
use App\DB\QueryResult;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Error;

class UpdateTaskServiceTypeErrTest extends TestCase
{
    private function createService(): UpdateTaskService
    {
        $mock = $this->createMock(TaskQueries::class);

        $mock->method('getTaskByID')
            ->willReturn(QueryResult::ok(['id' => 1, 'title' => 'dummy'], 1));

        $mock->method('updateTask')
            ->willReturn(QueryResult::ok(['id' => 1, 'title' => 'dummy updated'], 1));

        return new UpdateTaskService($mock);
    }

    public static function invalidRequestProvider(): array
    {
        return [
            // ---- id ----
            'id as string not numeric' => [
                ['id' => 'abc', 'title' => 'ok', 'user_id' => 'u1', 'is_done' => '1'],
                InvalidArgumentException::class,
            ],
            'id as object' => [
                ['id' => new \stdClass(), 'title' => 'ok', 'user_id' => 'u1', 'is_done' => '1'],
                InvalidArgumentException::class,
            ],

            // ---- title ----
            'title as empty string' => [
                ['id' => '1', 'title' => '', 'user_id' => 'u1', 'is_done' => '1'],
                InvalidArgumentException::class,
            ],
            'title as object' => [
                ['id' => '1', 'title' => new \stdClass(), 'user_id' => 'u1', 'is_done' => '1'],
                Error::class,
            ],

            // ---- user_id ----
            'user_id missing' => [
                ['id' => '1', 'title' => 'ok', 'is_done' => '1'],
                InvalidArgumentException::class,
            ],
            'user_id as object' => [
                ['id' => '1', 'title' => 'ok', 'user_id' => new \stdClass(), 'is_done' => '1'],
                Error::class, // object -> string ก็ Error
            ],

            // ---- is_done ----
            'is_done null' => [
                ['id' => '1', 'title' => 'ok', 'user_id' => 'u1', 'is_done' => null],
                InvalidArgumentException::class,
            ],
            'is_done string not numeric' => [
                ['id' => '1', 'title' => 'ok', 'user_id' => 'u1', 'is_done' => 'maybe'],
                Error::class,
            ],
            'is_done as object' => [
                ['id' => '1', 'title' => 'ok', 'user_id' => 'u1', 'is_done' => new \stdClass()],
                Error::class,
            ],
        ];
    }


    #[DataProvider('invalidRequestProvider')]
    public function testExecuteWithInvalidParams(array $body, string $expectedException): void
    {
        $service = $this->createService();

        $req = new Request('POST', '/tasks/update', [], null, $body);

        $this->expectException($expectedException);

        $service->execute($req);
    }
}
