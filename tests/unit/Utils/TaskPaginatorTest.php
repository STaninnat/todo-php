<?php

use PHPUnit\Framework\TestCase;
use App\Utils\TaskPaginator;
use App\DB\TaskQueries;
use App\DB\QueryResult;

/**
 * Unit tests for TaskPaginator
 *
 * Covers calculation of total pages based on:
 * 1. Query failure or invalid data (fallback to 1 page)
 * 2. Valid total task counts with different per-page values
 * Uses data providers to test multiple scenarios efficiently.
 */
class TaskPaginatorTest extends TestCase
{
    /**
     * Test calculateTotalPages() when query fails or returns invalid data
     *
     * - Uses a stub of TaskQueries that always fails
     * - Expects minimum 1 page as fallback
     *
     * @dataProvider failCasesProvider
     */
    public function testCalculateTotalPagesFailCase($description = ''): void
    {
        $stub = $this->createStub(TaskQueries::class);
        $stub->method('getTotalTasks')->willReturn(QueryResult::fail());

        $paginator = new TaskPaginator($stub);

        // Always returns 1 page on failure
        $this->assertSame(1, $paginator->calculateTotalPages(), $description);
    }

    /**
     * Test calculateTotalPages() with valid or edge-case total tasks
     *
     * - Uses data provider to test multiple combinations of totalTasks and perPage
     * - Verifies correct number of pages calculated
     *
     * @dataProvider okCasesProvider
     */
    public function testCalculateTotalPagesOkCases($totalTasks = null, $perPage = 10, $expectedPages = 1, $description = ''): void
    {
        $stub = $this->createStub(TaskQueries::class);
        $stub->method('getTotalTasks')->willReturn(QueryResult::ok($totalTasks));

        $paginator = new TaskPaginator($stub);

        // Assert calculated pages match expected
        $this->assertSame($expectedPages, $paginator->calculateTotalPages($perPage), $description);
    }

    /**
     * Data provider for fail cases
     *
     * - Simulates scenarios where query fails
     * - Each array element represents one test case
     *
     * @return array[]
     */
    public static function failCasesProvider(): array
    {
        return [
            ['Query fails'],    // Single fail case
        ];
    }

    /**
     * Data provider for OK/valid cases
     *
     * Each test case array contains:
     * - totalTasks: simulated total task count returned by query
     * - perPage: number of tasks per page
     * - expectedPages: expected output from calculateTotalPages()
     * - description: optional string to describe the case
     *
     * Covers edge cases:
     * - Non-integer data
     * - Zero or negative total tasks
     * - Exact multiples and remainders for per-page division
     */
    public static function okCasesProvider(): array
    {
        return [
            ['invalid', 10, 1, 'Data is a string'],
            [0, 10, 1, 'Data is 0'],
            [-10, 10, 1, 'Data is negative'],
            [20, 10, 2, '20 tasks with perPage 10'],
            [21, 10, 3, '21 tasks with perPage 10'],
            [25, 5, 5, '25 tasks with perPage 5'],
            [7, 1, 7, '7 tasks with perPage 1'],
            [5, 100, 1, '5 tasks with perPage 100'],
            [50, 50, 1, '50 tasks with perPage 50'],
            [1001, 10, 101, '1001 tasks with perPage 10'],
        ];
    }
}
