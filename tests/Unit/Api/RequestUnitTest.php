<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use App\Api\Request;

/**
 * Class RequestTest
 *
 * Unit tests for the Request class.
 *
 * This test suite verifies how the Request object initializes
 * and processes data from PHP superglobals, raw JSON input, and
 * query or POST data. It also checks helper methods for safely
 * retrieving parameters and queries.
 *
 * @package Tests\Unit\Api
 */
class RequestUnitTest extends TestCase
{
    /**
     * Test that Request correctly initializes from PHP superglobals.
     *
     * Simulates a normal request where $_SERVER, $_GET, and $_POST
     * provide request details. Ensures Request reads and normalizes
     * those values correctly.
     *
     * @return void
     */
    public function testDefaultsFromSuperglobals(): void
    {
        // Simulate global request state
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI']    = '/test/path';
        $_GET  = ['foo' => 'bar'];
        $_POST = ['name' => 'john'];

        // Create a Request using superglobals
        $req = new Request();

        // Validate all key properties
        $this->assertSame('POST', $req->method);
        $this->assertSame('/test/path', $req->path);
        $this->assertSame(['foo' => 'bar'], $req->query);
        $this->assertSame(['name' => 'john'], $req->body);
    }

    /**
     * Test that explicitly passed constructor arguments override superglobals.
     *
     * Ensures provided values (method, path, query, rawInput, post)
     * take precedence over any global state.
     *
     * @return void
     */
    public function testConstructorOverridesEverything(): void
    {
        $req = new Request(
            method: 'put',
            path: '/custom',
            query: ['a' => 1],
            rawInput: '{"x":10}',
            post: ['legacy' => 'y']
        );

        // Request should normalize method to uppercase
        $this->assertSame('PUT', $req->method);

        // Verify path and query override globals
        $this->assertSame('/custom', $req->path);
        $this->assertSame(['a' => 1], $req->query);

        // JSON body takes precedence over POST
        $this->assertSame(['x' => 10], $req->body);
    }

    /**
     * Test JSON parsing from raw input string.
     *
     * Ensures Request decodes valid JSON input into an associative array.
     *
     * @return void
     */
    public function testParseBodyWithJson(): void
    {
        $req = new Request(rawInput: '{"user":"alice"}');

        // JSON should be decoded properly
        $this->assertSame(['user' => 'alice'], $req->body);
    }

    /**
     * Test fallback to POST array when raw input is not valid JSON.
     *
     * Ensures Request uses provided POST data if raw input cannot be parsed.
     *
     * @return void
     */
    public function testParseBodyWithPostArray(): void
    {
        $req = new Request(rawInput: 'not-json', post: ['k' => 'v']);

        // Falls back to POST array
        $this->assertSame(['k' => 'v'], $req->body);
    }

    /**
     * Test URL-encoded form body parsing.
     *
     * Ensures Request correctly parses query-style strings like `x=1&y=2`
     * into key-value pairs.
     *
     * @return void
     */
    public function testParseBodyWithUrlEncodedString(): void
    {
        $req = new Request(rawInput: 'x=1&y=2');

        // Raw input should be parsed into array
        $this->assertSame(['x' => '1', 'y' => '2'], $req->body);
    }

    /**
     * Test helper methods for accessing params and query values.
     *
     * Ensures getParam() and getQuery() behave as expected, returning
     * null or default values when keys are missing.
     *
     * @return void
     */
    public function testHelperMethods(): void
    {
        $req = new Request(query: ['q' => 'search']);

        // Manually assign route parameters for simulation
        $req->params = ['id' => 42];

        // Ensure param and query helpers return correct values
        $this->assertSame(42, $req->getParam('id'));
        $this->assertNull($req->getParam('missing'));
        $this->assertSame('search', $req->getQuery('q'));
        $this->assertSame('default', $req->getQuery('missing', 'default'));
    }
}
