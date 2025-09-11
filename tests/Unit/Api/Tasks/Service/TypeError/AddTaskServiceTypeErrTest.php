<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Service\TypeError;

use App\Api\Tasks\Service\AddTaskService;
use App\DB\QueryResult;
use App\DB\TaskQueries;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Class AddTaskServiceTypeErrTest
 *
 * Unit tests to verify type errors in AddTaskService.
 *
 * This test suite ensures that invalid input types trigger TypeError,
 * protecting the service from unexpected input structures.
 *
 * @package Tests\Unit\Api\Tasks\Service\TypeError
 */
class AddTaskServiceTypeErrTest extends TestCase
{
    /** @var TaskQueries&\PHPUnit\Framework\MockObject\MockObject */
    private $taskQueries;

    private AddTaskService $service;

    /**
     * Setup mocks and service instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Mock TaskQueries to isolate service logic
        $this->taskQueries = $this->createMock(TaskQueries::class);

        // Always return a successful result for addTask to focus only on type errors
        $this->taskQueries
            ->method('addTask')
            ->willReturn(QueryResult::ok(['id' => 1], 1));

        $this->service = new AddTaskService($this->taskQueries);
    }

    /**
     * Test: execute() throws TypeError when input is not an array.
     *
     * @return void
     */
    public function testExecuteWithNonArrayInputThrowsTypeError(): void
    {
        $this->expectException(TypeError::class);

        // Sending a string instead of an array triggers TypeError
        /** @phpstan-ignore-next-line */
        $this->service->execute('not-an-array');
    }

    /**
     * Test: execute() throws TypeError when array contains invalid key types.
     *
     * @return void
     */
    public function testExecuteWithInvalidArrayKeyTypes(): void
    {
        $this->expectException(TypeError::class);

        // Passing 'user_id' as an array instead of a string triggers TypeError
        $this->service->execute([
            'title' => 'My Task',
            'description' => 'desc',
            'user_id' => ['wrong-type'],
        ]);
    }
}
