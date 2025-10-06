<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service\TypeError;

use App\Api\Tasks\Service\AddTaskService;
use App\Api\Request;
use App\DB\TaskQueries;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypeError;

class AddTaskServiceTypeErrTest extends TestCase
{
    #[DataProvider('provideInvalidConstructorArgs')]
    public function testConstructorThrowsTypeError($invalidArg): void
    {
        $this->expectException(TypeError::class);
        new AddTaskService($invalidArg);
    }

    public static function provideInvalidConstructorArgs(): array
    {
        return [
            'null' => [null],
            'int' => [123],
            'string' => ['not-a-task-queries'],
            'array' => [[]],
            'stdClass' => [new \stdClass()],
        ];
    }

    #[DataProvider('provideInvalidExecuteArgs')]
    public function testExecuteThrowsTypeError($invalidRequest): void
    {
        $mockTaskQueries = $this->createMock(TaskQueries::class);
        $service = new AddTaskService($mockTaskQueries);

        $this->expectException(TypeError::class);
        $service->execute($invalidRequest);
    }

    public static function provideInvalidExecuteArgs(): array
    {
        return [
            'null' => [null],
            'int' => [123],
            'string' => ['request'],
            'array' => [[]],
            'stdClass' => [new \stdClass()],
        ];
    }

    #[DataProvider('provideInvalidRequestBodies')]
    public function testExecuteWithInvalidRequestBodyThrowsTypeError(array $body): void
    {
        $mockTaskQueries = $this->createMock(TaskQueries::class);
        $service = new AddTaskService($mockTaskQueries);

        $raw = json_encode($body);
        $req = new Request('POST', '/tasks', [], $raw);

        $this->expectException(TypeError::class);
        $service->execute($req);
    }

    public static function provideInvalidRequestBodies(): array
    {
        return [
            'title is int'       => [['title' => 123, 'user_id' => 'u1']],
            'title is array'     => [['title' => ['bad'], 'user_id' => 'u1']],
            'user_id is int'     => [['title' => 'ok', 'user_id' => 456]],
            'user_id is object'  => [['title' => 'ok', 'user_id' => new \stdClass()]],
            'description array'  => [['title' => 'ok', 'user_id' => 'u1', 'description' => []]],
            'description object' => [['title' => 'ok', 'user_id' => 'u1', 'description' => new \stdClass()]],
        ];
    }
}
