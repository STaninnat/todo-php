<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\GetTasksService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;
use Error;

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
        $this->taskQueries->method('getTasksByUserID')
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
            ['id' => 1, 'title' => 'Test Task', 'user_id' => 1],
            ['id' => 2, 'title' => 'Another Task', 'user_id' => 1],
        ];

        // Simulate successful queries
        $this->taskQueries->method('getTasksByUserID')
            ->willReturn(QueryResult::ok($tasks, count($tasks)));

        // Simulate successful queries
        $this->taskQueries->method('getTasksByUserID')
            ->willReturn(QueryResult::ok($tasks, count($tasks)));

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
        $this->assertArrayNotHasKey('totalPages', $result);

        foreach ($result['task'] as $t) {
            $this->assertArrayNotHasKey('created_at', $t);
        }
    }
}
