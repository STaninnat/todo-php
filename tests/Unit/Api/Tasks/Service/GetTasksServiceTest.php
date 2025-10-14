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
class GetTasksServiceTest extends TestCase
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
     * Test that missing 'user_id' in request triggers InvalidArgumentException.
     *
     * @return void
     */
    public function testMissingUserIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $req = $this->makeRequest(); // Missing user_id field
        $this->service->execute($req);
    }

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

        $req = $this->makeRequest(['user_id' => '123']);
        $this->service->execute($req);
    }

    /**
     * Test that an empty successful query still triggers RuntimeException.
     *
     * Simulates QueryResult::ok([]) — no tasks found.
     *
     * @return void
     */
    public function testGetTasksReturnsNoDataThrowsRuntimeException(): void
    {
        $this->taskQueries->method('getTasksByUserID')
            ->willReturn(QueryResult::ok([]));

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(['user_id' => '123']);
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
            ['id' => 1, 'title' => 'Test Task'],
            ['id' => 2, 'title' => 'Another Task'],
        ];

        // Simulate successful queries
        $this->taskQueries->method('getTasksByUserID')
            ->willReturn(QueryResult::ok($tasks, count($tasks)));

        $this->taskQueries->method('getTotalTasks')
            ->willReturn(QueryResult::ok(20));

        // Build request with user_id
        $req = $this->makeRequest(['user_id' => '123']);

        // Execute service
        $result = $this->service->execute($req);

        // Assertions for structure and values
        $this->assertIsArray($result);
        $this->assertSame($tasks, $result['task']);
        $this->assertCount(2, $result['task']);
        $this->assertSame(2, $result['totalPages']); // ceil(20 / 10)
    }
}
