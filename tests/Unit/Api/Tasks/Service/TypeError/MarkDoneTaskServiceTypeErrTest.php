<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service\TypeError;

use App\Api\Request;
use App\Api\Tasks\Service\MarkDoneTaskService;
use App\DB\TaskQueries;
use App\DB\QueryResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use TypeError;
use RuntimeException;

class MarkDoneTaskServiceTypeErrTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private TaskQueries $taskQueriesMock;

    private MarkDoneTaskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taskQueriesMock = $this->createMock(TaskQueries::class);
        $this->service = new MarkDoneTaskService($this->taskQueriesMock);
    }

    public static function invalidIds(): array
    {
        return [
            // invalid → expect InvalidArgumentException
            'params array'     => [['bad'], 'params', InvalidArgumentException::class],
            'params object'    => [new \stdClass(), 'params', InvalidArgumentException::class],
            'params empty'     => ['', 'params', InvalidArgumentException::class],
            'params missing'   => [null, 'params', InvalidArgumentException::class],

            'query array'      => [['bad'], 'query', InvalidArgumentException::class],
            'query object'     => [new \stdClass(), 'query', InvalidArgumentException::class],
            'query empty'      => ['', 'query', InvalidArgumentException::class],
            'query missing'    => [null, 'query', InvalidArgumentException::class],

            'body array'       => [['bad'], 'body', InvalidArgumentException::class],
            'body object'      => [new \stdClass(), 'body', InvalidArgumentException::class],
            'body empty'       => ['', 'body', InvalidArgumentException::class],
            'body missing'     => [null, 'body', InvalidArgumentException::class],
        ];
    }


    #[DataProvider('invalidIds')]
    public function testExecuteWithInvalidId($id, string $source, string $expectedException): void
    {
        $request = new Request();
        if ($id !== null) {
            switch ($source) {
                case 'params':
                    $request->params['id'] = $id;
                    $request->params['user_id'] = 'u123';
                    $request->params['is_done'] = 1;
                    break;
                case 'query':
                    $request->query['id'] = $id;
                    $request->query['user_id'] = 'u123';
                    $request->query['is_done'] = 1;
                    break;
                case 'body':
                    $request->body['id'] = $id;
                    $request->body['user_id'] = 'u123';
                    $request->body['is_done'] = 1;
                    break;
            }
        } else {
            $request->params['user_id'] = 'u123';
            $request->params['is_done'] = 1;
        }

        $this->taskQueriesMock->method('getTaskByID')
            ->willReturn(QueryResult::fail(['forced error']));


        $this->expectException($expectedException);
        $this->service->execute($request);
    }

    public static function invalidUserIds(): array
    {
        return [
            'params int'     => [123, 'params', TypeError::class],
            'params array'   => [['bad'], 'params', TypeError::class],
            'params object'  => [new \stdClass(), 'params', TypeError::class],
            'params empty'   => ['', 'params', InvalidArgumentException::class],
            'params missing' => [null, 'params', InvalidArgumentException::class],

            'query int'      => [123, 'query', TypeError::class],
            'query array'    => [['bad'], 'query', TypeError::class],
            'query object'   => [new \stdClass(), 'query', TypeError::class],
            'query empty'    => ['', 'query', InvalidArgumentException::class],
            'query missing'  => [null, 'query', InvalidArgumentException::class],

            'body int'       => [123, 'body', TypeError::class],
            'body array'     => [['bad'], 'body', TypeError::class],
            'body object'    => [new \stdClass(), 'body', TypeError::class],
            'body empty'     => ['', 'body', InvalidArgumentException::class],
            'body missing'   => [null, 'body', InvalidArgumentException::class],
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
                    $request->params['id'] = 1;
                    $request->params['is_done'] = 1;
                    break;
                case 'query':
                    $request->query['user_id'] = $userId;
                    $request->query['id'] = 1;
                    $request->query['is_done'] = 1;
                    break;
                case 'body':
                    $request->body['user_id'] = $userId;
                    $request->body['id'] = 1;
                    $request->body['is_done'] = 1;
                    break;
            }
        } else {
            $request->params['id'] = 1;
            $request->params['is_done'] = 1;
        }

        $this->expectException($expectedException);
        $this->service->execute($request);
    }

    public function testExecuteWithValidDataButTaskNotFound(): void
    {
        $request = new Request();
        $request->params['id'] = 1;
        $request->params['user_id'] = 'u123';
        $request->params['is_done'] = 1;

        $this->taskQueriesMock->method('getTaskByID')
            ->willReturn(QueryResult::fail(['Task not found']));

        $this->expectException(RuntimeException::class);
        $this->service->execute($request);
    }
}
