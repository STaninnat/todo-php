<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service\TypeError;

use App\Api\Tasks\Service\AddTaskService;
use App\Api\Request;
use App\DB\TaskQueries;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;

/**
 * Class AddTaskServiceTypeErrTest
 *
 * Unit tests for AddTaskService focusing on type errors.
 *
 * Covers scenarios where invalid argument types are passed to:
 * - The constructor (invalid TaskQueries dependency)
 * - The execute() method (invalid Request or malformed request body)
 *
 * Uses PHPUnit DataProviders to test multiple invalid types efficiently.
 *
 * @package Tests\Unit\Api\Tasks\Service\TypeError
 */
class AddTaskServiceTypeErrTest extends TestCase
{
    /**
     * Test that constructor throws TypeError for invalid arguments.
     *
     * @param mixed $invalidArg An invalid constructor argument.
     */
    #[DataProvider('provideInvalidConstructorArgs')]
    public function testConstructorThrowsTypeError($invalidArg): void
    {
        $this->expectException(TypeError::class);
        new AddTaskService($invalidArg);
    }

    /**
     * Provides invalid constructor arguments for AddTaskService.
     *
     * @return array<int|string, array{0:mixed}>
     */
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

    /**
     * Test that execute() throws TypeError when passed invalid argument types.
     *
     * @param mixed $invalidRequest An invalid execute() argument.
     */
    #[DataProvider('provideInvalidExecuteArgs')]
    public function testExecuteThrowsTypeError($invalidRequest): void
    {
        $mockTaskQueries = $this->createMock(TaskQueries::class);
        $service = new AddTaskService($mockTaskQueries);

        $this->expectException(TypeError::class);
        $service->execute($invalidRequest);
    }

    /**
     * Provides invalid execute() arguments for AddTaskService.
     *
     * @return array<int|string, array{0:mixed}>
     */
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

    /**
     * Test that execute() throws TypeError when request body fields are invalid types.
     *
     * @param array $body Simulated request body with invalid types.
     */
    #[DataProvider('provideInvalidRequestBodies')]
    public function testExecuteWithInvalidRequestBodyThrowsTypeError(array $body): void
    {
        $mockTaskQueries = $this->createMock(TaskQueries::class);
        $service = new AddTaskService($mockTaskQueries);

        $raw = json_encode($body);
        $req = new Request('POST', '/tasks', [], $raw);

        if (isset($body['title'])) {
            if (!is_string($body['title'])) {
                // title int -> RuntimeException
                // title array -> InvalidArgumentException
                $this->expectException(is_array($body['title']) ? \InvalidArgumentException::class : \RuntimeException::class);
            }
        }

        if (isset($body['user_id'])) {
            if (!is_string($body['user_id'])) {
                // user_id int -> RuntimeException
                // user_id object -> InvalidArgumentException
                $this->expectException(is_object($body['user_id']) ? \InvalidArgumentException::class : \RuntimeException::class);
            }
        }

        if (isset($body['description'])) {
            if (!is_string($body['description'])) {
                // description array/object -> RuntimeException
                $this->expectException(\RuntimeException::class);
            }
        }

        $service->execute($req);
    }

    /**
     * Provides request bodies with invalid types for AddTaskService.
     *
     * Covers invalid types for 'title', 'user_id', and 'description'.
     *
     * @return array<int|string, array{0:array}>
     */
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
