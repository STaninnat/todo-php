<?php

declare(strict_types=1);

namespace Tests\Unit\Api\TypeError;

use PHPUnit\Framework\TestCase;
use App\Api\Request;
use TypeError;

/**
 * Class RequestTypeErrTest
 *
 * Unit tests to ensure that the Request class enforces strict typing.
 *
 * These tests verify that passing incorrect data types to the Request
 * constructor or its methods consistently triggers TypeError exceptions,
 * while valid input behaves as expected.
 *
 * @package Tests\Unit\Api\TypeError
 */
class RequestTypeErrTest extends TestCase
{
    /**
     * Test that passing a non-string value for method triggers TypeError.
     *
     * @return void
     */
    public function testThrowsTypeErrorWhenMethodIsNotString(): void
    {
        $this->expectException(TypeError::class);

        // Invalid: method should be a string, not an array
        new Request(method: ['GET']);
    }

    /**
     * Test that passing a non-string value for path triggers TypeError.
     *
     * @return void
     */
    public function testThrowsTypeErrorWhenPathIsNotString(): void
    {
        $this->expectException(TypeError::class);

        // Invalid: path must be string
        new Request(method: 'GET', path: ['path']);
    }

    /**
     * Test that passing a non-array value for query triggers TypeError.
     *
     * @return void
     */
    public function testThrowsTypeErrorWhenQueryIsNotArray(): void
    {
        $this->expectException(TypeError::class);

        // Invalid: query should be an array
        new Request(method: 'GET', path: '/test', query: 'not-array');
    }

    /**
     * Test that passing a non-string rawInput triggers TypeError.
     *
     * @return void
     */
    public function testThrowsTypeErrorWhenRawInputIsNotString(): void
    {
        $this->expectException(TypeError::class);

        // Invalid: rawInput must be string or null
        new Request(method: 'POST', path: '/x', query: [], rawInput: ['invalid']);
    }

    /**
     * Test that passing a non-array value for post triggers TypeError.
     *
     * @return void
     */
    public function testThrowsTypeErrorWhenPostIsNotArray(): void
    {
        $this->expectException(TypeError::class);

        // Invalid: post should be array
        new Request(method: 'POST', path: '/x', query: [], rawInput: '{}', post: 'invalid');
    }

    /**
     * Test that getParam() throws TypeError when key is not a string.
     *
     * @return void
     */
    public function testThrowsTypeErrorWhenGetParamKeyIsNotString(): void
    {
        $req = new Request(method: 'GET', path: '/');

        $this->expectException(TypeError::class);

        // Invalid key type (int instead of string)
        $req->getParam(123);
    }

    /**
     * Test that getQuery() throws TypeError when key is not a string.
     *
     * @return void
     */
    public function testThrowsTypeErrorWhenGetQueryKeyIsNotString(): void
    {
        $req = new Request(method: 'GET', path: '/');

        $this->expectException(TypeError::class);

        // Invalid key type (int instead of string)
        $req->getQuery(123);
    }

    /**
     * Test that constructing with valid types works without errors.
     *
     * Ensures proper initialization when all arguments are of correct types.
     *
     * @return void
     */
    public function testConstructWithValidTypesDoesNotThrow(): void
    {
        $req = new Request(
            method: 'POST',
            path: '/users',
            query: ['page' => '1'],
            rawInput: '{"key":"value"}',
            post: ['form' => 'data']
        );

        // Object should be created successfully
        $this->assertInstanceOf(Request::class, $req);
        $this->assertSame('POST', $req->method);
        $this->assertSame('/users', $req->path);
    }

    /**
     * Test getParam() with valid key returns correct value without error.
     *
     * @return void
     */
    public function testGetParamWithValidStringKeyDoesNotThrow(): void
    {
        $req = new Request(method: 'GET', path: '/');

        // Set mock route parameters
        $req->params = ['id' => 10];

        // Should retrieve correct value safely
        $result = $req->getParam('id');
        $this->assertSame(10, $result);
    }

    /**
     * Test getQuery() with valid key returns correct value without error.
     *
     * @return void
     */
    public function testGetQueryWithValidStringKeyDoesNotThrow(): void
    {
        $req = new Request(method: 'GET', path: '/', query: ['name' => 'John']);

        // Should return the expected query value
        $result = $req->getQuery('name');
        $this->assertSame('John', $result);
    }
}
