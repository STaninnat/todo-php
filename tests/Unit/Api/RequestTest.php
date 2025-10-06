<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use App\Api\Request;

class RequestTest extends TestCase
{
    public function testDefaultsFromSuperglobals()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI']    = '/test/path';
        $_GET  = ['foo' => 'bar'];
        $_POST = ['name' => 'john'];

        $req = new Request();

        $this->assertSame('POST', $req->method);
        $this->assertSame('/test/path', $req->path);
        $this->assertSame(['foo' => 'bar'], $req->query);
        $this->assertSame(['name' => 'john'], $req->body);
    }

    public function testConstructorOverridesEverything()
    {
        $req = new Request(
            method: 'put',
            path: '/custom',
            query: ['a' => 1],
            rawInput: '{"x":10}',
            post: ['legacy' => 'y']
        );

        $this->assertSame('PUT', $req->method);
        $this->assertSame('/custom', $req->path);
        $this->assertSame(['a' => 1], $req->query);
        $this->assertSame(['x' => 10], $req->body);
    }

    public function testParseBodyWithJson()
    {
        $req = new Request(rawInput: '{"user":"alice"}');
        $this->assertSame(['user' => 'alice'], $req->body);
    }

    public function testParseBodyWithPostArray()
    {
        $req = new Request(rawInput: 'not-json', post: ['k' => 'v']);
        $this->assertSame(['k' => 'v'], $req->body);
    }

    public function testParseBodyWithUrlEncodedString()
    {
        $req = new Request(rawInput: 'x=1&y=2');
        $this->assertSame(['x' => '1', 'y' => '2'], $req->body);
    }

    public function testHelperMethods()
    {
        $req = new Request(query: ['q' => 'search']);
        $req->params = ['id' => 42];

        $this->assertSame(42, $req->getParam('id'));
        $this->assertNull($req->getParam('missing'));
        $this->assertSame('search', $req->getQuery('q'));
        $this->assertSame('default', $req->getQuery('missing', 'default'));
    }
}
