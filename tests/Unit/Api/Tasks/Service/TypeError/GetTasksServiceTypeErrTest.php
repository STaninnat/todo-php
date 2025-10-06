<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service\TypeError;

use App\Api\Request;
use App\Api\Tasks\Service\GetTasksService;
use App\DB\TaskQueries;
use App\DB\QueryResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use TypeError;

class GetTasksServiceTypeErrTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private TaskQueries $taskQueriesMock;

    /** @var GetTasksService&\PHPUnit\Framework\MockObject\MockObject */
    private GetTasksService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskQueriesMock = $this->createMock(TaskQueries::class);
        $this->service = new GetTasksService($this->taskQueriesMock);
    }

    public static function invalidUserIds(): array
    {
        return [
            // params
            'params int'         => [123, 'params', TypeError::class],
            'params array'       => [['bad'], 'params', TypeError::class],
            'params object'      => [new \stdClass(), 'params', TypeError::class],
            'params empty'       => ['', 'params', InvalidArgumentException::class],
            'params missing'     => [null, 'params', InvalidArgumentException::class],

            // query
            'query int'          => [123, 'query', TypeError::class],
            'query array'        => [['bad'], 'query', TypeError::class],
            'query object'       => [new \stdClass(), 'query', TypeError::class],
            'query empty'        => ['', 'query', InvalidArgumentException::class],
            'query missing'      => [null, 'query', InvalidArgumentException::class],

            // body
            'body int'           => [123, 'body', TypeError::class],
            'body array'         => [['bad'], 'body', TypeError::class],
            'body object'        => [new \stdClass(), 'body', TypeError::class],
            'body empty'         => ['', 'body', InvalidArgumentException::class],
            'body missing'       => [null, 'body', InvalidArgumentException::class],
        ];
    }

    #[DataProvider('invalidUserIds')]
    public function testExecuteWithInvalidUserId($userId, string $source, string $expectedException): void
    {
        $request = new Request();

        if ($userId !== null) {
            switch ($source) {
                case 'params':
                    $request->params['user_id'] = $userId;
                    break;
                case 'query':
                    $request->query['user_id'] = $userId;
                    break;
                case 'body':
                    $request->body['user_id'] = $userId;
                    break;
            }
        }

        $this->expectException($expectedException);
        $this->service->execute($request);
    }

    public function testExecuteWithValidUserIdButMockFails(): void
    {
        $request = new Request();
        $request->params['user_id'] = 'u123';

        // mock ให้ return failure ของ QueryResult
        $this->taskQueriesMock->method('getTasksByUserID')
            ->willReturn(QueryResult::fail(['DB error']));

        $this->expectException(\RuntimeException::class);
        $this->service->execute($request);
    }
}
