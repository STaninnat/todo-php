<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Utils\TaskPaginator;
use App\DB\TaskQueries;
use App\DB\QueryResult;

/**
 * Class TaskPaginatorTest
 *
 * Unit tests for the TaskPaginator class.
 *
 * Covers calculation of total pages based on:
 * - Query failure or invalid data (fallback to 1 page)
 * - Valid total task counts with different per-page values
 *
 * Uses data providers to test multiple scenarios efficiently.
 *
 * @package Tests\Unit\Utils
 */
class TaskPaginatorUnitTest extends TestCase
{
    /**
     * Test that calculateTotalPages() returns 1 when query fails or returns invalid data.
     *
     * @param string $description Description of the fail case.
     *
     * @return void
     */
    #[DataProvider('failCasesProvider')]
    public function testCalculateTotalPagesFailCase($description = ''): void
    {
        $stub = $this->createStub(TaskQueries::class);
        $stub->method('getTotalTasks')->willReturn(QueryResult::fail());

        $paginator = new TaskPaginator($stub);

        // Always returns 1 page on failure
        $this->assertSame(1, $paginator->calculateTotalPages(), $description);
    }

    /**
     * Test that calculateTotalPages() works with valid or edge-case total task counts.
     *
     * @param mixed  $totalTasks     Simulated total task count.
     * @param int    $perPage        Number of tasks per page.
     * @param int    $expectedPages  Expected number of calculated pages.
     * @param string $description    Description of the test case.
     *
     * @return void
     */
    #[DataProvider('okCasesProvider')]
    public function testCalculateTotalPagesOkCases($totalTasks = null, $perPage = 10, $expectedPages = 1, $description = ''): void
    {
        $stub = $this->createStub(TaskQueries::class);
        $stub->method('getTotalTasks')->willReturn(QueryResult::ok($totalTasks));

        $paginator = new TaskPaginator($stub);

        // Assert calculated pages match expected
        $this->assertSame($expectedPages, $paginator->calculateTotalPages($perPage), $description);
    }

    /**
     * Provides fail cases for calculateTotalPages().
     *
     * Each case simulates a scenario where the query fails.
     *
     * @return array<int, array{0:string}>
     */
    public static function failCasesProvider(): array
    {
        return [
            ['Query fails'],    // Single fail case
        ];
    }

    /**
     * Provides valid and edge-case scenarios for calculateTotalPages().
     *
     * Each test case array contains:
     * - totalTasks: simulated total task count
     * - perPage: number of tasks per page
     * - expectedPages: expected calculated pages
     * - description: description of the case
     *
     * Covers edge cases:
     * - Non-integer data
     * - Zero or negative task counts
     * - Exact multiples and remainders for per-page division
     *
     * @return array<int, array{0:mixed,1:int,2:int,3:string}>
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
