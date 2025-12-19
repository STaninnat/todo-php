<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Middlewares;

use App\Api\Middlewares\DebugMiddleware;
use App\Api\Request;
use App\Utils\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class DebugMiddlewareIntegrationTest
 *
 * Integration tests for {@see DebugMiddleware}, ensuring that
 * logged messages match expected output. Covers:
 *
 * - Standard request logging for method, path, query, body, and route params
 * - Logging of JSON decoding errors when malformed payloads are provided
 *
 * @package Tests\Integration\Api\Middlewares
 */
class DebugMiddlewareIntegrationTest extends TestCase
{
    /** @var DebugMiddleware Middleware instance under test */
    private DebugMiddleware $middleware;

    /** @var Logger|MockObject Mocked logger used for asserting log calls */
    private Logger|MockObject $logger;

    /**
     * Prepare the test environment.
     *
     * Creates a mocked Logger and initializes DebugMiddleware.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(Logger::class);
        $this->middleware = new DebugMiddleware($this->logger);
    }

    /**
     * Test that DebugMiddleware logs the expected debug information
     * in the correct sequence for a valid incoming request.
     *
     * Validates:
     * - Initial and ending log markers
     * - HTTP method and path
     * - Query, body, and route parameters
     *
     * @return void
     */
    public function testLogging(): void
    {
        $request = new Request('POST', '/test', ['q' => '1'], null, ['b' => '2']);
        $request->params = ['id' => '123'];

        $matcher = $this->exactly(7);
        /** @phpstan-ignore method.notFound */
        $this->logger->expects($matcher)
            ->method('info')
            ->willReturnCallback(function (string $message) use ($matcher) {
                $expected = [
                    '=== Debug Middleware ===',
                    'Request Method: POST',
                    'Request Path: /test',
                    'Query Params: {"q":"1"}',
                    'Body Params: {"b":"2"}',
                    'Route Params: {"id":"123"}',
                    '========================'
                ];

                /** @phpstan-ignore method.internalClass */
                $invocation = $matcher->numberOfInvocations();
                $this->assertSame($expected[$invocation - 1], $message);
            });

        ($this->middleware)($request);
    }

    /**
     * Test that DebugMiddleware logs a JSON decoding warning when
     * the incoming request contains malformed JSON input.
     *
     * Ensures:
     * - jsonError is detected by {@see Request}
     * - A warning log entry containing "JSON Decode Error" is generated
     *
     * @return void
     */
    public function testJsonErrorLogging(): void
    {
        $request = new Request('POST', '/test', [], '{invalid-json}', []);

        /** @phpstan-ignore method.notFound */
        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('JSON Decode Error'));

        ($this->middleware)($request);
    }

    /**
     * Test that DebugMiddleware correctly masks sensitive data in logs.
     *
     * Ensures:
     * - Keys like password/token are masked with ***
     * - Structure remains intact
     *
     * @return void
     */
    public function testLoggingSanitizesSensitiveData(): void
    {
        $request = new Request(
            'POST',
            '/login',
            [],
            null,
            ['username' => 'admin', 'password' => 'secret']
        );

        $matcher = $this->exactly(6);
        /** @phpstan-ignore method.notFound */
        $this->logger->expects($matcher)
            ->method('info')
            ->willReturnCallback(function (string $message) use ($matcher) {
                $expected = [
                    '=== Debug Middleware ===',
                    'Request Method: POST',
                    'Request Path: /login',
                    'Query Params: []',
                    'Body Params: {"username":"admin","password":"***"}',
                    '========================'
                ];

                /** @phpstan-ignore method.internalClass */
                $invocation = $matcher->numberOfInvocations();
                $this->assertSame($expected[$invocation - 1], $message);
            });

        ($this->middleware)($request);
    }
}
