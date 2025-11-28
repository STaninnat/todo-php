<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\JsonResponder;

/**
 * Class JsonResponderTest
 *
 * Unit tests for the JsonResponder class.
 *
 * This test suite verifies:
 * - Response creation and type handling
 * - Data/payload inclusion and overriding
 * - Pagination and HTTP status handling
 * - Behavior in CLI mode
 * - Quick helper methods (quickSuccess, quickError, quickInfo)
 *
 * @package Tests\Unit\Utils
 */
class JsonResponderUnitTest extends TestCase
{
    /**
     * Test the success() factory method with default type and HTTP status.
     * 
     * Ensures 'success' response is correctly built.
     * 
     * @return void
     */
    public function testSuccessConstructorDefaultTypeAndHttpStatus(): void
    {
        // Create a success response with default type/status
        $responder = JsonResponder::success('OK message');
        $array = $responder->toArray();

        // Assert success response structure
        $this->assertTrue($array['success']);
        $this->assertSame('success', $array['type']);
        $this->assertSame('OK message', $array['message']);
    }

    /**
     * Test the error() factory method with default type and HTTP status.
     * 
     * Ensures 'error' response is correctly built.
     * 
     * @return void
     */
    public function testErrorConstructorDefaultTypeAndHttpStatus(): void
    {
        // Create an error response with default type/status
        $responder = JsonResponder::error('Error occurred');
        $array = $responder->toArray();

        // Assert error response structure
        $this->assertFalse($array['success']);
        $this->assertSame('error', $array['type']);
        $this->assertSame('Error occurred', $array['message']);
    }

    /**
     * Test the info() factory method.
     * 
     * Ensures 'info' response is correctly built and defaults to unsuccessful.
     * 
     * @return void
     */
    public function testInfoConstructorDefaultType(): void
    {
        // Create an info response
        $responder = JsonResponder::info('Just info');
        $array = $responder->toArray();

        // Info defaults to unsuccessful
        $this->assertFalse($array['success']);
        $this->assertSame('info', $array['type']);
        $this->assertSame('Just info', $array['message']);
    }

    /**
     * Test that an invalid type passed to success() defaults to 'info'.
     * 
     * @return void
     */
    public function testInvalidTypeFallbackToInfo(): void
    {
        // Pass an invalid type; should fallback to 'info'
        $responder = JsonResponder::success('Weird type', 'weird');
        $array = $responder->toArray();

        $this->assertSame('info', $array['type']);
    }

    /**
     * Test adding data and payload to the response.
     * 
     * Ensures the last call to withPayload() overrides previous data correctly.
     * 
     * @return void
     */
    public function testWithDataAndPayload(): void
    {
        // Attach data first, then override with payload
        $responder = JsonResponder::success('With data')
            ->withData(['foo' => 'bar'])
            ->withPayload(['baz' => 123]);

        $array = $responder->toArray();

        // Payload should replace data
        $this->assertArrayHasKey('data', $array);
        $this->assertSame(['baz' => 123], $array['data']);
    }



    /**
     * Test forcing the type of the response using withType().
     * 
     * Should correctly override initial type.
     * 
     * @return void
     */
    public function testWithTypeValid(): void
    {
        // Force change type from error to success
        $responder = JsonResponder::error('Force type')->withType('success');
        $array = $responder->toArray();

        $this->assertSame('success', $array['type']);
    }

    /**
     * Test setting a custom HTTP status code.
     * 
     * Uses reflection to access private property for verification.
     * 
     * @return void
     */
    public function testWithHttpStatus(): void
    {
        // Set custom HTTP status
        $responder = JsonResponder::success('Custom')->withHttpStatus(201);

        // Use reflection to access private property for verification
        $ref = new \ReflectionClass($responder);
        $prop = $ref->getProperty('httpStatus');
        $prop->setAccessible(true);

        $this->assertSame(201, $prop->getValue($responder));
    }

    /**
     * Test sending the response in test mode (non-output mode).
     * 
     * Should return the array representation instead of echoing JSON.
     * 
     * @return void
     */
    public function testSendReturnsArrayTestMode(): void
    {
        $responder = JsonResponder::success('Send test')->withData(['x' => 1]);
        $result = $responder->send(false, true);

        $this->assertSame('Send test', $result['message']);
        $this->assertSame(['x' => 1], $result['data']);
    }

    /**
     * Test quickSuccess() static helper.
     * 
     * Returns an array immediately without sending HTTP response.
     * 
     * @return void
     */
    public function testQuickSuccessReturnsArray(): void
    {
        // Quick helper for success, returns array directly
        $result = JsonResponder::quickSuccess('Q message', false, true);

        $this->assertSame('Q message', $result['message']);
        $this->assertTrue($result['success']);
    }

    /**
     * Test quickError() static helper.
     * 
     * @return void
     */
    public function testQuickErrorReturnsArray(): void
    {
        // Quick helper for error
        $result = JsonResponder::quickError('Bad thing', false, true);

        $this->assertSame('Bad thing', $result['message']);
        $this->assertFalse($result['success']);
    }

    /**
     * Test quickInfo() static helper.
     * 
     * Should return array with type 'info'.
     * 
     * @return void
     */
    public function testQuickInfoReturnsArray(): void
    {
        // Quick helper for info
        $result = JsonResponder::quickInfo('FYI', false, true);

        $this->assertSame('FYI', $result['message']);
        $this->assertSame('info', $result['type']);
    }
}
