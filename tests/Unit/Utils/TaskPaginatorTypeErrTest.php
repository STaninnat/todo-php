<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\TaskPaginator;
use App\DB\TaskQueries;

final class TaskPaginatorTypeErrTest extends TestCase
{
    /**
     * Test constructor throws TypeError if injected dependency is not TaskQueries
     */
    public function testConstructorThrowsTypeErrorOnInvalidDependency(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the taskQueries as a string instead of a TaskQueries will cause a TypeError.
        new TaskPaginator('not-a-taskqueries');
    }

    /**
     * Test calculateTotalPages throws TypeError if perPage is not int
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
