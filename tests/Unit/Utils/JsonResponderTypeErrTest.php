<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\JsonResponder;

/**
 * JsonResponderTypeErrorTest
 *
 * Separate tests to check strict typing enforcement.
 * Ensures TypeError is thrown when invalid types are passed.
 */
class JsonResponderTypeErrTest extends TestCase
{
    /**
     * Test that success() throws TypeError when message is not a string.
     */
    public function testSuccessThrowsTypeErrorOnInvalidMessage(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the msg as a string instead of an int will cause a TypeError.
        $invalidMessage = 12345;
        JsonResponder::success($invalidMessage);
    }

    /**
     * Test that error() throws TypeError when message is not a string.
     */
    public function testErrorThrowsTypeErrorOnInvalidMessage(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the msg as a null instead of a string will cause a TypeError.
        $invalidMessage = null;
        JsonResponder::error($invalidMessage);
    }

    /**
     * Test that info() throws TypeError when message is not a string.
     */
    public function testInfoThrowsTypeErrorOnInvalidMessage(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the msg as an array instead of a string will cause a TypeError.
        $invalidMessage = [];
        JsonResponder::info($invalidMessage);
    }

    /**
     * Test that withData() throws TypeError when argument is not an array.
     */
    public function testWithDataThrowsTypeErrorOnNonArray(): void
    {
        $this->expectException(\TypeError::class);

        $responder = JsonResponder::success('msg');

        // Sending the data as a string instead of an array will cause a TypeError.
        $invalidData = 'not-an-array';
        $responder->withData($invalidData);
    }

    /**
     * Test that withPayload() throws TypeError when argument is not an array.
     */
    public function testWithPayloadThrowsTypeErrorOnNonArray(): void
    {
        $this->expectException(\TypeError::class);

        $responder = JsonResponder::success('msg');

        // Sending the payload as a string instead of an array will cause a TypeError.
        $invalidData = 'string-instead-of-array';
        $responder->withPayload($invalidData);
    }

    /**
     * Test that withTotalPages() throws TypeError when argument is not an int.
     */
    public function testWithTotalPagesThrowsTypeErrorOnNonInt(): void
    {
        $this->expectException(\TypeError::class);

        $responder = JsonResponder::success('msg');

        // Sending the total as a string instead of an int will cause a TypeError.
        $invalidTotal = '10';
        $responder->withTotalPages($invalidTotal);
    }

    /**
     * Test that withType() throws TypeError when argument is not a string.
     */
    public function testWithTypeThrowsTypeErrorOnNonString(): void
    {
        $this->expectException(\TypeError::class);

        $responder = JsonResponder::success('msg');

        // Sending the total as an int instead of a string will cause a TypeError.
        $invalidType = 123;
        $responder->withType($invalidType);
    }

    /**
     * Test that withHttpStatus() throws TypeError when argument is not an int.
     */
    public function testWithHttpStatusThrowsTypeErrorOnNonInt(): void
    {
        $this->expectException(\TypeError::class);

        $responder = JsonResponder::success('msg');

        // Sending the total as a string instead of an int will cause a TypeError.
        $invalidStatus = '200';
        $responder->withHttpStatus($invalidStatus);
    }
}
