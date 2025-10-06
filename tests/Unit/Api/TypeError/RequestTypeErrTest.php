<?php

declare(strict_types=1);

namespace Tests\Unit\Api\TypeError;

use PHPUnit\Framework\TestCase;
use App\Api\Request;
use TypeError;

class RequestTypeErrTest extends TestCase
{
    public function testThrowsTypeErrorWhenMethodIsNotString(): void
    {
        $this->expectException(TypeError::class);
        new Request(method: ['GET']); // invalid type
    }

    public function testThrowsTypeErrorWhenPathIsNotString(): void
    {
        $this->expectException(TypeError::class);
        new Request(method: 'GET', path: ['path']);
    }

    public function testThrowsTypeErrorWhenQueryIsNotArray(): void
    {
        $this->expectException(TypeError::class);
        new Request(method: 'GET', path: '/test', query: 'not-array');
    }

    public function testThrowsTypeErrorWhenRawInputIsNotString(): void
    {
        $this->expectException(TypeError::class);
        new Request(method: 'POST', path: '/x', query: [], rawInput: ['invalid']);
    }

    public function testThrowsTypeErrorWhenPostIsNotArray(): void
    {
        $this->expectException(TypeError::class);
        new Request(method: 'POST', path: '/x', query: [], rawInput: '{}', post: 'invalid');
    }

    public function testThrowsTypeErrorWhenGetParamKeyIsNotString(): void
    {
        $req = new Request(method: 'GET', path: '/');
        $this->expectException(TypeError::class);
        $req->getParam(123);
    }

    public function testThrowsTypeErrorWhenGetQueryKeyIsNotString(): void
    {
        $req = new Request(method: 'GET', path: '/');
        $this->expectException(TypeError::class);
        $req->getQuery(123);
    }

    public function testConstructWithValidTypesDoesNotThrow(): void
    {
        $req = new Request(
            method: 'POST',
            path: '/users',
            query: ['page' => '1'],
            rawInput: '{"key":"value"}',
            post: ['form' => 'data']
        );

        $this->assertInstanceOf(Request::class, $req);
        $this->assertSame('POST', $req->method);
        $this->assertSame('/users', $req->path);
    }

    public function testGetParamWithValidStringKeyDoesNotThrow(): void
    {
        $req = new Request(method: 'GET', path: '/');
        $req->params = ['id' => 10];

        $result = $req->getParam('id');
        $this->assertSame(10, $result);
    }

    public function testGetQueryWithValidStringKeyDoesNotThrow(): void
    {
        $req = new Request(method: 'GET', path: '/', query: ['name' => 'John']);
        $result = $req->getQuery('name');
        $this->assertSame('John', $result);
    }
}
