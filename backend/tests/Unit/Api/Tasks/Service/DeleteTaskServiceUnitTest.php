<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Service\DeleteTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class DeleteTaskServiceTest
 *
 * Unit tests for the DeleteTaskService class.
 *
 * Ensures correct validation and behavior for task deletion requests,
 * including:
 * - Input validation for required fields
 * - Handling of failed or unchanged delete operations
 * - Correct response for successful deletion
 *
 * @package Tests\Unit\Api\Tasks\Service
 */
class DeleteTaskServiceUnitTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject Mocked TaskQueries instance. */
    private $taskQueries;

    /** @var DeleteTaskService Service under test. */
    private DeleteTaskService $service;

    /**
     * Sets up test dependencies before each test.
     *
     * Creates mock objects for TaskQueries and initializes
     * the DeleteTaskService instance.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new DeleteTaskService($this->taskQueries);
    }

    use ApiTestHelperTrait;

    /**
     * Test that missing task ID throws an InvalidArgumentException.
     *
     * Ensures the service validates required parameters correctly.
     *
     * @return void
     */
    public function testMissingTaskIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Missing 'id' in request body
        $req = $this->makeRequest(['user_id' => '123']);
        $this->service->execute($req);
    }



    /**
     * Test that non-numeric task ID throws an InvalidArgumentException.
     *
     * Validates numeric input requirement for the 'id' field.
     *
     * @return void
     */
    public function testNonNumericTaskIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Invalid 'id' value (should be numeric)
        $req = $this->makeRequest(['id' => 'abc'], [], [], 'POST', '/', ['id' => '123']);
        $this->service->execute($req);
    }

    /**
     * Test that a failed delete query triggers a RuntimeException.
     *
     * Simulates a database error during task deletion.
     *
     * @return void
     */
    public function testDeleteFailsReturnsRuntimeException(): void
    {
        $this->taskQueries
            ->expects($this->once()) // Expect one call to deleteTask()
            ->method('deleteTask')
            ->willReturn(QueryResult::fail(['SQL error']));

        $this->expectException(RuntimeException::class);

        // Both 'id' and 'user_id' are provided
        $req = $this->makeRequest(['id' => '1'], [], [], 'POST', '/', ['id' => '123']);
        $this->service->execute($req);
    }

    /**
     * Test that deleteTask() returning no changed rows triggers RuntimeException.
     *
     * Covers the case where the query succeeded but did not delete any records.
     *
     * @return void
     */
    public function testDeleteNotChangedThrowsRuntimeException(): void
    {
        $this->taskQueries
            ->expects($this->once())
            ->method('deleteTask')
            ->willReturn(QueryResult::ok(null, 0)); // 0 rows affected

        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(['id' => '1'], [], [], 'POST', '/', ['id' => '123']);
        $this->service->execute($req);
    }

    /**
     * Test successful deletion returns expected result array.
     *
     * Validates correct handling of both deletion and total task recalculation.
     *
     * Expected response structure:
     * [
     *   'id' => (int),
     *   'totalPages' => (int)
     * ]
     *
     * @return void
     */
    public function testDeleteTaskSuccessReturnsExpectedArray(): void
    {
        // Simulate successful deletion (1 row affected)
        $this->taskQueries
            ->expects($this->once())
            ->method('deleteTask')
            ->willReturn(QueryResult::ok(null, 1));



        // Valid request body
        $req = $this->makeRequest(['id' => '1'], [], [], 'POST', '/', ['id' => '123']);
        $result = $this->service->execute($req);

        // Verify structure and values of the returned array
        $this->assertSame(1, $result['id']);
        $this->assertArrayNotHasKey('totalPages', $result);
    }
}
