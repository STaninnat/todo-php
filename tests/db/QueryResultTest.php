<?php
require_once __DIR__ . '/../../src/db/QueryResult.php';

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the QueryResult class.
 *
 * This test suite verifies:
 * - Success and failure factory methods (ok/fail)
 * - Behavior of utility methods (isChanged, hasData)
 * - Handling of affected rows, data, and error information
 */
class QueryResultTest extends TestCase
{
    /**
     * Test: ok() without data should produce a successful result
     * with no data, no error, and no affected changes.
     */
    public function testOkWithoutData()
    {
        $result = QueryResult::ok();

        // Should mark as success
        $this->assertTrue($result->success);

        // No rows affected
        $this->assertSame(0, $result->affected);

        // No data or error returned
        $this->assertNull($result->data);
        $this->assertNull($result->error);

        // Neither changed nor contains data
        $this->assertFalse($result->isChanged());
        $this->assertFalse($result->hasData());
    }

    /**
     * Test: ok() with data should produce a successful result
     * containing the provided data and hasData() should return true.
     */
    public function testOkWithData()
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $result = QueryResult::ok($data);

        // Should mark as success
        $this->assertTrue($result->success);

        // Data should match what was provided
        $this->assertSame($data, $result->data);

        // hasData() should detect non-empty dataset
        $this->assertTrue($result->hasData());
    }

    /**
     * Test: ok() with affected rows should mark result as changed.
     */
    public function testOkWithAffectedRows()
    {
        $result = QueryResult::ok(null, 3);

        // Should mark as success
        $this->assertTrue($result->success);

        // Number of affected rows should be set
        $this->assertSame(3, $result->affected);

        // isChanged() should return true when affected > 0
        $this->assertTrue($result->isChanged());
    }

    /**
     * Test: fail() without error should produce an unsuccessful result
     * with no data, no error, and no changes.
     */
    public function testFailWithoutError()
    {
        $result = QueryResult::fail();

        // Should mark as failure
        $this->assertFalse($result->success);

        // No rows affected and no data
        $this->assertSame(0, $result->affected);
        $this->assertNull($result->data);

        // No error info provided
        $this->assertNull($result->error);

        // Should not be marked as changed or having data
        $this->assertFalse($result->isChanged());
        $this->assertFalse($result->hasData());
    }

    /**
     * Test: fail() with error should include error information.
     */
    public function testFailWithError()
    {
        $error = ['code' => '123', 'message' => 'DB error'];
        $result = QueryResult::fail($error);

        // Should mark as failure
        $this->assertFalse($result->success);

        // Error info should be preserved
        $this->assertSame($error, $result->error);
    }


    /**
     * Test: hasData() should return false for empty array.
     */
    public function testHasDataWithEmptyData()
    {
        $result = QueryResult::ok([]);

        // Empty array should be treated as "no data"
        $this->assertFalse($result->hasData());
    }

    /**
     * Test: hasData() should return true for non-empty array.
     */
    public function testHasDataWithNonEmptyData()
    {
        $result = QueryResult::ok(['foo' => 'bar']);

        // Non-empty array should be treated as "has data"
        $this->assertTrue($result->hasData());
    }

    /**
     * Test: isChanged() should return false when affected = 0.
     */
    public function testIsChangedWhenAffectedZero()
    {
        $result = QueryResult::ok(null, 0);

        // Affected = 0 should not count as changed
        $this->assertFalse($result->isChanged());
    }

    /**
     * Test: isChanged() should return true when affected > 0.
     */
    public function testIsChangedWhenAffectedPositive()
    {
        $result = QueryResult::ok(null, 2);

        // Affected > 0 should count as changed
        $this->assertTrue($result->isChanged());
    }
}
