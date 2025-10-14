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

/**
 * Class UpdateTaskServiceTypeErrTest
 *
 * Unit tests for UpdateTaskService focusing on type errors and invalid input.
 *
 * Covers scenarios where:
 * - id, title, user_id, or is_done have invalid types
 * - Missing or null values are provided
 *
 * Uses data provider to test multiple invalid request cases efficiently.
 *
 * @package Tests\Unit\Api\Tasks\Service\TypeError
 */
class UpdateTaskServiceTypeErrTest extends TestCase
{
    /**
     * Creates a service instance with mocked TaskQueries.
     *
     * Stubs getTaskByID and updateTask to return dummy results,
     * allowing tests to focus purely on input validation/type checks.
     *
     * @return UpdateTaskService
     */
    private function createService(): UpdateTaskService
    {
        $mock = $this->createMock(TaskQueries::class);

        // Return a dummy task for getTaskByID
        $mock->method('getTaskByID')
            ->willReturn(QueryResult::ok(['id' => 1, 'title' => 'dummy'], 1));

        // Return updated dummy task for updateTask
        $mock->method('updateTask')
            ->willReturn(QueryResult::ok(['id' => 1, 'title' => 'dummy updated'], 1));

        return new UpdateTaskService($mock);
    }

    /**
     * Provides invalid request payloads and the expected exceptions.
     *
     * Covers various edge cases for each field:
     * - id: non-numeric string, object
     * - title: empty string, object
     * - user_id: missing, object
     * - is_done: null, non-numeric string, object
     *
     * @return array<int|string, array{0:array,1:string}>
     */
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
                Error::class, // object passed instead of string triggers PHP Error
            ],

            // ---- user_id ----
            'user_id missing' => [
                ['id' => '1', 'title' => 'ok', 'is_done' => '1'],
                InvalidArgumentException::class,
            ],
            'user_id as object' => [
                ['id' => '1', 'title' => 'ok', 'user_id' => new \stdClass(), 'is_done' => '1'],
                Error::class, // object -> string conversion fails
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

    /**
     * Test that executing the service with invalid parameters throws expected exceptions.
     *
     * @param array  $body               Request body payload
     * @param string $expectedException  Expected exception class
     *
     * @return void
     */
    #[DataProvider('invalidRequestProvider')]
    public function testExecuteWithInvalidParams(array $body, string $expectedException): void
    {
        $service = $this->createService();

        // Construct request object with provided invalid body
        $req = new Request('POST', '/tasks/update', [], null, $body);

        // Assert that executing service triggers expected exception
        $this->expectException($expectedException);

        $service->execute($req);
    }
}
