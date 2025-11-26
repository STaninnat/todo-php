<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Middlewares;

use App\Api\Middlewares\DebugMiddleware;
use App\Api\Request;
use App\Utils\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class DebugMiddlewareTest
 *
 * Unit tests for {@see DebugMiddleware}. Ensures that detailed request
 * information is logged in the correct order and that JSON parsing errors
 * are reported through the logger.
 *
 * - Verifies method/path/query/body/params logging
 * - Ensures logging sequence matches middleware output
 * - Confirms JSON decode errors are logged via `warning()`
 *
 * @package Tests\Unit\Api\Middlewares
 */
class DebugMiddlewareTest extends TestCase
{
    /** @var DebugMiddleware Middleware instance under test */
    private DebugMiddleware $middleware;

    /** @var Logger|MockObject Mocked logger instance */
    private Logger|MockObject $logger;

    /**
     * Setup test environment.
     *
     * Creates a mocked Logger and binds it to a DebugMiddleware instance.
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
     * Ensure that request details are logged in the correct sequence.
     *
     * - Logs method, path, query, body, route params
     * - Uses an invocation matcher to assert exact order
     *
     * @return void
     */
    public function testInvokeLogsRequestDetails(): void
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
     * Ensure JSON parsing errors are logged correctly.
     *
     * - Simulates raw invalid JSON
     * - Expects a single `warning()` log containing the JSON error message
     *
     * @return void
     */
    public function testInvokeLogsJsonError(): void
    {
        $request = new Request('POST', '/test', [], '{invalid-json}', []);

        /** @phpstan-ignore method.notFound */
        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('JSON Decode Error'));

        ($this->middleware)($request);
    }
}
