<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service\TypeError;

use App\Api\Tasks\Service\UpdateTaskService;
use App\DB\TaskQueries;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Class UpdateTaskServiceTypeErrTest
 *
 * Unit tests to verify type errors in UpdateTaskService.
 *
 * Ensures that invalid input types or constructor arguments
 * trigger TypeError, protecting the service from unexpected input.
 *
 * @package Tests\Unit\Api\Tasks\Service\TypeError
 */
class UpdateTaskServiceTypeErrTest extends TestCase
{
    private UpdateTaskService $service;

    /**
     * Setup service instance with mocked TaskQueries.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Mock TaskQueries to isolate service behavior
        $taskQueries = $this->createMock(TaskQueries::class);
        $this->service = new UpdateTaskService($taskQueries);
    }

    /**
     * Test: execute() throws TypeError when input is not an array.
     *
     * @return void
     */
    public function testExecuteWithNonArrayInputThrowsTypeError(): void
    {
        $this->expectException(TypeError::class);

        // Passing a string instead of an array triggers TypeError
        /** @phpstan-ignore-next-line */
        $this->service->execute('not-an-array');
    }

    /**
     * Test: constructor throws TypeError when TaskQueries is not provided.
     *
     * @return void
     */
    public function testConstructorWithInvalidTaskQueriesTypeThrowsTypeError(): void
    {
        $this->expectException(TypeError::class);

        // Passing invalid type to constructor triggers TypeError
        /** @phpstan-ignore-next-line */
        new UpdateTaskService('not-a-TaskQueries');
    }
}
