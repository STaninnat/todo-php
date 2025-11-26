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
use RuntimeException;
use TypeError;

/**
 * Class GetTasksServiceTypeErrTest
 *
 * Unit tests for GetTasksService that specifically cover type errors
 * and invalid input scenarios for the user_id field.
 *
 * Scenarios include:
 * - user_id passed in params, query, or body as invalid types
 * - user_id missing or empty
 * - Mocked DB failures during execution
 *
 * Uses a data provider to cover multiple invalid input cases efficiently.
 *
 * @package Tests\Unit\Api\Tasks\Service\TypeError
 */
class GetTasksServiceTypeErrTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject Mocked TaskQueries dependency */
    private TaskQueries $taskQueriesMock;

    /** @var GetTasksService&\PHPUnit\Framework\MockObject\MockObject Service under test */
    private GetTasksService $service;

    /**
     * Set up the test environment.
     *
     * Creates a mocked TaskQueries instance and initializes
     * the GetTasksService with it.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->taskQueriesMock = $this->createMock(TaskQueries::class);
        $this->service = new GetTasksService($this->taskQueriesMock);
    }

    /**
     * Provides invalid user_id values for different sources (params, query, body)
     *
     * Covers:
     * - Incorrect types (int, array, object)
     * - Empty strings
     * - Null (missing values)
     *
     * @return array<string, array{0:mixed,1:string,2:string}>
     */
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

    /**
     * Test execution with invalid user_id values.
     *
     * Uses the data provider to test multiple scenarios:
     * - Invalid types (TypeError expected)
     * - Missing or empty values (InvalidArgumentException expected)
     *
     * @param mixed  $userId            Value to test
     * @param string $source            Source of the user_id (params, query, body)
     * @param string $expectedException Expected exception class
     */
    #[DataProvider('invalidUserIds')]
    public function testExecuteWithInvalidUserId($userId, string $source, string $expectedException): void
    {
        $request = new Request();

        // Assign user_id to the correct source if not null
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

        // Expect the correct exception depending on input
        if ($userId === null || $userId === '') {
            $this->expectException(InvalidArgumentException::class);
        } elseif (is_int($userId)) {
            $this->expectException(RuntimeException::class);
        } elseif (is_array($userId) || is_object($userId)) {
            $this->expectException(InvalidArgumentException::class);
        }

        $this->service->execute($request);
    }

    /**
     * Test execution with valid user_id but database mock fails.
     *
     * Ensures that service correctly throws RuntimeException
     * when the underlying TaskQueries returns a failure.
     */
    public function testExecuteWithValidUserIdButMockFails(): void
    {
        $request = new Request();
        $request->params['user_id'] = 'u123';

        // Mock getTasksByUserID to return a failure
        $this->taskQueriesMock->method('getTasksByUserID')
            ->willReturn(QueryResult::fail(['DB error']));

        $this->expectException(\RuntimeException::class);
        $this->service->execute($request);
    }
}
