<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service\TypeError;

use App\Api\Tasks\Service\DeleteTaskService;
use App\Api\Request;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypeError;

class DeleteTaskServiceTypeErrTest extends TestCase
{
    // ---------- Constructor TypeError ----------
    #[DataProvider('provideInvalidConstructorArgs')]
    public function testConstructorThrowsTypeError($invalidArg): void
    {
        $this->expectException(TypeError::class);
        new DeleteTaskService($invalidArg);
    }

    public static function provideInvalidConstructorArgs(): array
    {
        return [
            'null'      => [null],
            'int'       => [123],
            'string'    => ['not-a-task-queries'],
            'array'     => [[]],
            'stdClass'  => [new \stdClass()],
        ];
    }

    // ---------- Execute argument TypeError ----------
    #[DataProvider('provideInvalidExecuteArgs')]
    public function testExecuteThrowsTypeError($invalidRequest): void
    {
        $mockTaskQueries = $this->createMock(TaskQueries::class);
        $service = new DeleteTaskService($mockTaskQueries);

        $this->expectException(TypeError::class);
        $service->execute($invalidRequest);
    }

    public static function provideInvalidExecuteArgs(): array
    {
        return [
            'null'      => [null],
            'int'       => [123],
            'string'    => ['request'],
            'array'     => [[]],
            'stdClass'  => [new \stdClass()],
        ];
    }

    // ---------- Body field validation (InvalidArgumentException) ----------
    #[DataProvider('provideInvalidRequestBodies')]
    public function testExecuteWithInvalidRequestBody($body, string $expectedException): void
    {
        $mockTaskQueries = $this->createMock(TaskQueries::class);
        $service = new DeleteTaskService($mockTaskQueries);

        $raw = json_encode($body);
        $req = new Request('POST', '/tasks/delete', [], $raw);

        $this->expectException($expectedException);
        $service->execute($req);
    }

    public static function provideInvalidRequestBodies(): array
    {
        return [
            // ---- id invalid ----
            'id is string-non-numeric' => [['id' => 'not-a-number', 'user_id' => 'u1'], InvalidArgumentException::class],
            'id is array'              => [['id' => ['bad'], 'user_id' => 'u1'], InvalidArgumentException::class],
            'id is object'             => [['id' => new \stdClass(), 'user_id' => 'u1'], InvalidArgumentException::class],

            // ---- user_id invalid (TypeError จาก strip_tags) ----
            'user_id is int'           => [['id' => 1, 'user_id' => 123], TypeError::class],
            'user_id is array'         => [['id' => 1, 'user_id' => ['bad']], TypeError::class],
            'user_id is object'        => [['id' => 1, 'user_id' => new \stdClass()], TypeError::class],

            // ---- missing fields ----
            'missing id'               => [['user_id' => 'u1'], InvalidArgumentException::class],
            'missing user_id'          => [['id' => 1], InvalidArgumentException::class],
            'empty body'               => [[], InvalidArgumentException::class],
        ];
    }
}
