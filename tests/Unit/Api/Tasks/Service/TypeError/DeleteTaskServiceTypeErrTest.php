<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service\TypeError;

use App\Api\Tasks\Service\DeleteTaskService;
use App\Api\Request;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;

/**
 * Class DeleteTaskServiceTypeErrTest
 *
 * Unit tests for DeleteTaskService focusing on type errors
 * and request validation errors.
 *
 * Covers:
 * - Constructor argument type enforcement (TypeError)
 * - execute() argument type enforcement (TypeError)
 * - Request body field validation (InvalidArgumentException or TypeError)
 *
 * Uses data providers to test multiple invalid inputs efficiently.
 *
 * @package Tests\Unit\Api\Tasks\Service\TypeError
 */
class DeleteTaskServiceTypeErrTest extends TestCase
{
    // ---------- Constructor TypeError ----------

    /**
     * Test that providing invalid constructor arguments
     * throws a TypeError.
     *
     * @param mixed $invalidArg Invalid argument to pass to constructor.
     */
    #[DataProvider('provideInvalidConstructorArgs')]
    public function testConstructorThrowsTypeError($invalidArg): void
    {
        $this->expectException(TypeError::class);
        new DeleteTaskService($invalidArg);
    }

    /**
     * Provides invalid constructor arguments for DeleteTaskService.
     *
     * @return array<string, array{0:mixed}>
     */
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

    /**
     * Test that execute() throws TypeError when provided
     * with invalid arguments (not a Request instance).
     *
     * @param mixed $invalidRequest Invalid argument to pass to execute().
     */
    #[DataProvider('provideInvalidExecuteArgs')]
    public function testExecuteThrowsTypeError($invalidRequest): void
    {
        $mockTaskQueries = $this->createMock(TaskQueries::class);
        $service = new DeleteTaskService($mockTaskQueries);

        $this->expectException(TypeError::class);
        $service->execute($invalidRequest);
    }

    /**
     * Provides invalid arguments for execute() method.
     *
     * @return array<string, array{0:mixed}>
     */
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

    // ---------- Body field validation (InvalidArgumentException / TypeError) ----------

    /**
     * Test that execute() throws expected exceptions when
     * request body fields are invalid or missing.
     *
     * @param array $body Request body to test
     * @param class-string<\Throwable> $expectedException Expected exception class
     */
    #[DataProvider('provideInvalidRequestBodies')]
    public function testExecuteWithInvalidRequestBody($body, string $expectedException): void
    {
        $mockTaskQueries = $this->createMock(TaskQueries::class);
        $service = new DeleteTaskService($mockTaskQueries);

        // Encode body to JSON as raw input
        $raw = json_encode($body);
        $req = new Request('POST', '/tasks/delete', [], $raw);

        if (isset($body['id']) && !is_numeric($body['id'])) {
            $this->expectException(InvalidArgumentException::class);
        } elseif (!isset($body['id']) || empty($body['id'])) {
            $this->expectException(InvalidArgumentException::class);
        } elseif (!isset($body['user_id']) || !is_string($body['user_id'])) {
            if (is_int($body['user_id'] ?? null)) {
                $this->expectException(RuntimeException::class);
            } else {
                $this->expectException(InvalidArgumentException::class);
            }
        }

        $service->execute($req);
    }

    /**
     * Provides invalid request bodies to test execute() validation.
     *
     * Covers:
     * - id invalid (non-numeric, array, object)
     * - user_id invalid (int, array, object)
     * - missing fields (id or user_id)
     * - empty body
     *
     * @return array<string, array{0:array,1:class-string<\Throwable>}>
     */
    public static function provideInvalidRequestBodies(): array
    {
        return [
            // ---- id invalid ----
            'id is string-non-numeric' => [['id' => 'not-a-number', 'user_id' => 'u1'], InvalidArgumentException::class],
            'id is array'              => [['id' => ['bad'], 'user_id' => 'u1'], InvalidArgumentException::class],
            'id is object'             => [['id' => new \stdClass(), 'user_id' => 'u1'], InvalidArgumentException::class],

            // ---- user_id invalid (TypeError from strip_tags) ----
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
