<?php

declare(strict_types=1);

namespace Tests\Unit\DB\TypeError;

use PHPUnit\Framework\TestCase;
use App\DB\QueryResult;

/**
 * Class QueryResultTypeErrTest
 *
 * Unit tests for QueryResult to ensure strict typing enforcement.
 * Tests that TypeError is thrown when invalid types are passed to static methods.
 *
 * @package Tests\Unit\DB\TypeError
 */
class QueryResultTypeErrTest extends TestCase
{
    /**
     * Test that QueryResult::ok() throws TypeError when affected parameter is not an int.
     *
     * @return void
     */
    public function testOkWithInvalidAffectedType(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the affected as a string instead of an int will cause a TypeError.
        QueryResult::ok(null, "invalid");
    }

    /**
     * Test that QueryResult::fail() throws TypeError when error parameter is not an array or null.
     *
     * @return void
     */
    public function testFailWithInvalidErrorType(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the error as a string instead of ?array will cause a TypeError.
        QueryResult::fail("not-an-array");
    }

    /**
     * Test that QueryResult::ok() accepts various data types for the data parameter.
     *
     * mixed type allows any value, so TypeError won't be triggered.
     * This test confirms correct assignment of different types.
     *
     * @return void
     */
    public function testOkWithVariousDataTypes(): void
    {
        $result = QueryResult::ok(123);                     // int
        $this->assertSame(123, $result->data);

        $result = QueryResult::ok("string");                // string
        $this->assertSame("string", $result->data);

        $result = QueryResult::ok([]);                      // array
        $this->assertSame([], $result->data);

        $result = QueryResult::ok((object)['a' => 1]);      // object
        $this->assertEquals((object)['a' => 1], $result->data);
    }
}
