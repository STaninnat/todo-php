<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service\TypeError;

use App\Api\Request;
use App\Api\Tasks\Service\MarkDoneTaskService;
use App\DB\TaskQueries;
use App\DB\QueryResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Error;
use InvalidArgumentException;
use TypeError;
use RuntimeException;

/**
 * Class MarkDoneTaskServiceTypeErrTest
 *
 * Unit tests for MarkDoneTaskService focusing on type errors
 * and invalid input handling.
 *
 * Covers scenarios including:
 * - Invalid or missing task IDs
 * - Invalid or missing user IDs
 * - Task not found errors
 *
 * Uses data providers to test multiple invalid input types efficiently.
 *
 * @package Tests\Unit\Api\Tasks\Service\TypeError
 */
class MarkDoneTaskServiceTypeErrTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject Mocked TaskQueries dependency */
    private TaskQueries $taskQueriesMock;

    /** @var MarkDoneTaskService Service under test */
    private MarkDoneTaskService $service;

    /**
     * Set up the test environment.
     *
     * Creates a mocked TaskQueries instance and initializes
     * the MarkDoneTaskService with it.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->taskQueriesMock = $this->createMock(TaskQueries::class);
        $this->service = new MarkDoneTaskService($this->taskQueriesMock);
    }

    /**
     * Provides invalid task ID inputs for data-driven testing.
     *
     * Each entry contains:
     * - The invalid ID value
     * - Source location (params, query, or body)
     * - Expected exception class
     *
     * @return array<string, array{0:mixed,1:string,2:string}>
     */
    public static function invalidIds(): array
    {
        return [
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

    /**
     * Test execution with invalid task IDs.
     *
     * Uses the invalidIds data provider.
     *
     * @param mixed  $id                The invalid ID value
     * @param string $source            Source location (params, query, body)
     * @param string $expectedException Expected exception class
     */
    #[DataProvider('invalidIds')]
    public function testExecuteWithInvalidId($id, string $source, string $expectedException): void
    {
        $request = new Request();

        // Assign the invalid ID based on the source
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
            // No ID provided → use default params
            $request->params['user_id'] = 'u123';
            $request->params['is_done'] = 1;
        }

        // Mock getTaskByID to simulate DB failure
        $this->taskQueriesMock->method('getTaskByID')
            ->willReturn(QueryResult::fail(['forced error']));

        $this->expectException($expectedException);
        $this->service->execute($request);
    }

    /**
     * Provides invalid user ID inputs for data-driven testing.
     *
     * Each entry contains:
     * - The invalid user ID value
     * - Source location (params, query, body)
     * - Expected exception class
     *
     * @return array<string, array{0:mixed,1:string,2:string}>
     */
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

    /**
     * Test execution with invalid user IDs.
     *
     * Uses the invalidUserIds data provider.
     *
     * @param mixed  $userId            The invalid user ID value
     * @param string $source            Source location (params, query, body)
     * @param string $expectedException Expected exception class
     */
    #[DataProvider('invalidUserIds')]
    public function testExecuteWithInvalidUserId($userId, string $source, string $expectedException): void
    {
        $request = new Request();

        // Assign the invalid user ID based on the source
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
            // No user_id provided → default params
            $request->params['id'] = 1;
            $request->params['is_done'] = 1;
        }

        if ($userId === null || $userId === '') {
            $this->expectException(InvalidArgumentException::class);
        } elseif (is_array($userId) || is_object($userId)) {
            $this->expectException(InvalidArgumentException::class);
        } else {
            $this->expectException(Error::class);
        }

        $this->service->execute($request);
    }

    /**
     * Test execution with valid IDs but task not found in DB.
     *
     * Ensures RuntimeException is thrown on task lookup failure.
     */
    public function testExecuteWithValidDataButTaskNotFound(): void
    {
        $request = new Request();
        $request->params['id'] = 1;
        $request->params['user_id'] = 'u123';
        $request->params['is_done'] = 1;

        // Simulate task not found
        $this->taskQueriesMock->method('getTaskByID')
            ->willReturn(QueryResult::fail(['Task not found']));

        $this->expectException(RuntimeException::class);
        $this->service->execute($request);
    }
}
