<?php

declare(strict_types=1);

namespace Tests\Unit\Utils\TypeError;

use PHPUnit\Framework\TestCase;
use App\Utils\TaskPaginator;
use App\DB\TaskQueries;

/**
 * Class TaskPaginatorTypeErrTest
 *
 * Unit tests for TaskPaginator to ensure strict typing enforcement.
 * Tests that TypeError is thrown when invalid types are passed to constructor or methods.
 *
 * @package Tests\Unit\Utils\TypeError
 */
final class TaskPaginatorTypeErrTest extends TestCase
{
    /**
     * Test constructor throws TypeError when injected dependency is not TaskQueries.
     *
     * @return void
     */
    public function testConstructorThrowsTypeErrorOnInvalidDependency(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the taskQueries as a string instead of a TaskQueries instance will cause a TypeError.
        new TaskPaginator('not-a-taskqueries');
    }

    /**
     * Test calculateTotalPages() throws TypeError when perPage is not an int.
     *
     * @return void
     */
    public function testCalculateTotalPagesThrowsTypeErrorOnInvalidPerPage(): void
    {
        $stub = $this->createStub(TaskQueries::class);
        $paginator = new TaskPaginator($stub);

        $this->expectException(\TypeError::class);

        // Sending the perPage as a string instead of an int will cause a TypeError.
        $paginator->calculateTotalPages('not-an-int');
    }
}
