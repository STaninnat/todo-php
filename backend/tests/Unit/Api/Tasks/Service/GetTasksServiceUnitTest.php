<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\GetTasksService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use RuntimeException;

/**
 * Class GetTasksServiceTest
 *
 * Unit tests for the GetTasksService class.
 *
 * Covers behavior for:
 * - Missing or invalid request parameters
 * - Query failure scenarios
 * - Empty data responses
 * - Successful data retrieval with expected output
 *
 * Uses ApiTestHelperTrait to simplify Request creation.
 *
 * @package Tests\Unit\Api\Tasks\Service
 */
class GetTasksServiceUnitTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    /** @var GetTasksService Service under test */
    private GetTasksService $service;

    /**
     * Set up mock dependencies before each test.
     *
     * Creates a mock TaskQueries and injects it
     * into the GetTasksService instance.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new GetTasksService($this->taskQueries);
    }

    use ApiTestHelperTrait;



    /**
     * Test that a database query failure triggers RuntimeException.
     *
     * Simulates failure in getTasksByUserID().
     *
     * @return void
     */
    public function testGetTasksFailsThrowsRuntimeException(): void
    {
        // Mock query returning failure result
        $this->taskQueries->method('getTasksByPage')
            ->willReturn(QueryResult::fail(['DB error']));

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest([], [], [], 'GET', '/', ['id' => '123']);
        $this->service->execute($req);
    }

    /**
     * Test that successful query returns expected array structure.
     *
     * Verifies both task data and calculated pagination (totalPages).
     *
     * @return void
     */
    public function testGetTasksSuccessReturnsExpectedArray(): void
    {
        // Sample mock task data
        $tasks = [
            ['id' => 1, 'title' => 'Test Task', 'user_id' => 1, 'created_at' => '2023-01-01'],
            ['id' => 2, 'title' => 'Another Task', 'user_id' => 1, 'created_at' => '2023-01-01'],
        ];

        // Simulate successful queries
        $this->taskQueries->method('getTasksByPage')
            ->willReturn(QueryResult::ok($tasks, count($tasks)));

        $this->taskQueries->method('countTasksByUserId')
            ->willReturn(count($tasks));


        // Build request with auth
        $req = $this->makeRequest([], [], [], 'GET', '/', ['id' => '123']);

        // Execute service
        $result = $this->service->execute($req);

        // Assertions for structure and values
        $expectedTasks = array_map(function ($t) {
            unset($t['user_id']);
            unset($t['created_at']);
            return $t;
        }, $tasks);

        $this->assertEquals($expectedTasks, $result['task']);
        $this->assertCount(2, $result['task']);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertEquals(1, $result['pagination']['current_page']);
        foreach ($result['task'] as $t) {
            $this->assertArrayNotHasKey('created_at', $t);
        }
    }

    /**
     * Test that search query is passed to TaskQueries.
     * 
     * @return void
     */
    public function testGetTasksWithSearchSuccess(): void
    {
        $tasks = [['id' => 10, 'title' => 'Search Result']];

        // Expect getTasksByPage to be called with search term 'apple' and null status
        $this->taskQueries->expects($this->once())
            ->method('getTasksByPage')
            ->with(1, 10, 'u1', 'apple', null)
            ->willReturn(QueryResult::ok($tasks, 1));

        $this->taskQueries->method('countTasksByUserId')
            ->with('u1', 'apple', null)
            ->willReturn(1);

        $req = $this->makeRequest([], ['search' => 'apple'], [], 'GET', '/', ['id' => 'u1']);

        $result = $this->service->execute($req);

        $this->assertCount(1, $result['task']);
        $this->assertEquals('Search Result', $result['task'][0]['title']);
    }

    /**
     * Test filtering by active status.
     */
    public function testGetTasksWithActiveStatus(): void
    {
        $this->taskQueries->expects($this->once())
            ->method('getTasksByPage')
            ->with(1, 10, 'u1', null, false) // false = active
            ->willReturn(QueryResult::ok([], 0));

        $req = $this->makeRequest([], ['status' => 'active'], [], 'GET', '/', ['id' => 'u1']);
        $this->service->execute($req);
    }

    /**
     * Test filtering by completed status.
     */
    public function testGetTasksWithCompletedStatus(): void
    {
        $this->taskQueries->expects($this->once())
            ->method('getTasksByPage')
            ->with(1, 10, 'u1', null, true) // true = completed
            ->willReturn(QueryResult::ok([], 0));

        $req = $this->makeRequest([], ['status' => 'completed'], [], 'GET', '/', ['id' => 'u1']);
        $this->service->execute($req);
    }
}

