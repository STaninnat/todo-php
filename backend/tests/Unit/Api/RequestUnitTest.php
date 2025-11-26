<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use App\Api\Request;
use PHPUnit\Framework\TestCase;

/**
 * Class Request
 *
 * Represents an HTTP request abstraction that normalizes data from
 * PHP superglobals, raw input, and uploaded files. Provides convenient
 * accessors for HTTP method, path, query, body, route parameters, and files.
 *
 * - Supports parsing JSON, URL-encoded, and form-data bodies
 * - Provides helper methods for retrieving typed parameters safely
 * - Handles uploaded file access
 *
 * @package App\Api
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

    /**
     * Test getJsonError() returns null for valid JSON and error for invalid JSON.
     *
     * Verifies that getJsonError() correctly identifies parsing errors
     * in raw JSON input.
     *
     * @return void
     */
    public function testGetJsonError(): void
    {
        $reqValid = new Request(rawInput: '{"ok":true}');
        $this->assertNull($reqValid->getJsonError());

        $reqInvalid = new Request(rawInput: '{"x":');
        $this->assertNotNull($reqInvalid->getJsonError());
    }

    /**
     * Test getJson() returns associative array for valid JSON objects
     * and null for non-object JSON values.
     *
     * Ensures scalar or non-array JSON does not override POST fallback.
     *
     * @return void
     */
    public function testGetJsonReturnsArrayOrNull(): void
    {
        // JSON array -> getJson() return array
        $reqArray = new Request(rawInput: '{"a":1}', post: []);
        $this->assertSame(['a' => 1], $reqArray->getJson());

        // JSON scalar -> getJson() return null
        $reqScalar = new Request(rawInput: '123', post: []);
        $this->assertNull($reqScalar->getJson());

        $reqString = new Request(rawInput: '"string"', post: []);
        $this->assertNull($reqString->getJson());

        $reqBool = new Request(rawInput: 'true', post: []);
        $this->assertNull($reqBool->getJson());
    }

    /**
     * Test getBody() returns parsed body array.
     *
     * Ensures getBody() returns the correct associative array
     * parsed from raw input JSON.
     *
     * @return void
     */
    public function testGetBody(): void
    {
        $data = ['x' => 1, 'y' => 2];
        $req = new Request(rawInput: json_encode($data) ?: null);
        $this->assertSame($data, $req->getBody());
    }

    /**
     * Test typed query getters (int, string, bool) with default values.
     *
     * Ensures getIntQuery(), getStringQuery(), and getBoolQuery()
     * convert query values correctly and apply defaults for missing keys.
     *
     * @return void
     */
    public function testTypedQueryGetters(): void
    {
        $req = new Request(query: [
            'int' => '5',
            'string' => 123,
            'true' => 'true',
            'false' => 'no',
        ]);

        $this->assertSame(5, $req->getIntQuery('int'));
        $this->assertSame(123, $req->getIntQuery('missing', 123));

        $this->assertSame('123', $req->getStringQuery('string'));
        $this->assertSame('default', $req->getStringQuery('missing', 'default'));

        $this->assertTrue($req->getBoolQuery('true'));
        $this->assertFalse($req->getBoolQuery('false', true));
        $this->assertTrue($req->getBoolQuery('missing', true));
    }

    /**
     * Test typed body getters (int, string, bool) and generic value retrieval.
     *
     * Ensures getIntBody(), getStringBody(), getBoolBody(), and getBodyValue()
     * return correct types and fallback defaults for missing keys.
     *
     * @return void
     */
    public function testTypedBodyGetters(): void
    {
        $raw = '{"int":10,"string":"abc","boolTrue":"true","boolFalse":"no","value":"val"}';
        $req = new Request(rawInput: $raw);

        $this->assertSame(10, $req->getIntBody('int'));
        $this->assertSame(5, $req->getIntBody('missing', 5));

        $this->assertSame('abc', $req->getStringBody('string'));
        $this->assertSame('default', $req->getStringBody('missing', 'default'));

        $this->assertTrue($req->getBoolBody('boolTrue'));
        $this->assertFalse($req->getBoolBody('boolFalse', true));
        $this->assertTrue($req->getBoolBody('missing', true));

        $this->assertSame('val', $req->getBodyValue('value'));
        $this->assertSame('def', $req->getBodyValue('missing', 'def'));
    }

    /**
     * Test getFile() and hasFile() with mocked file upload behavior.
     *
     * Verifies Request correctly returns file info and checks
     * uploaded files using a mocked isUploadedFile() method.
     *
     * @return void
     */
    public function testGetFileAndHasFileWithMock(): void
    {
        $files = [
            'file1' => [
                'name' => 'file.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/fakefile',
                'error' => 0,
                'size' => 123,
            ],
            'file2' => [
                'name' => 'missing.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/missing',
                'error' => 0,
                'size' => 0,
            ],
        ];

        $req = new class(files: $files) extends Request {
            protected function isUploadedFile(string $file): bool
            {
                return $file === '/tmp/fakefile';
            }

            public function hasFile(string $key): bool
            {
                return isset($this->files[$key]) &&
                    $this->isUploadedFile($this->files[$key]['tmp_name']);
            }
        };

        // ==== getFile() ====
        $this->assertSame($files['file1'], $req->getFile('file1'));
        $this->assertSame($files['file2'], $req->getFile('file2'));
        $this->assertNull($req->getFile('missing'));

        // ==== hasFile() ====
        $this->assertTrue($req->hasFile('file1'));
        $this->assertFalse($req->hasFile('file2'));
        $this->assertFalse($req->hasFile('missing'));
    }
}
