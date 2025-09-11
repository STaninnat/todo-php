<?php

declare(strict_types=1);

namespace Tests\Unit\Utils\TypeError;

use PHPUnit\Framework\TestCase;
use App\Utils\JsonResponder;

/**
 * Class JsonResponderTypeErrTest
 *
 * Unit tests for JsonResponder to ensure strict typing enforcement.
 * Tests that TypeError is thrown when invalid types are passed to methods.
 *
 * @package Tests\Unit\Utils\TypeError
 */
class JsonResponderTypeErrTest extends TestCase
{
    /**
     * Test that success() throws TypeError when message is not a string.
     *
     * @return void
     */
    public function testSuccessThrowsTypeErrorOnInvalidMessage(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the message as an int instead of a string will cause a TypeError.
        $invalidMessage = 12345;

        /** @phpstan-ignore-next-line */
        JsonResponder::success($invalidMessage);
    }

    /**
     * Test that error() throws TypeError when message is not a string.
     *
     * @return void
     */
    public function testErrorThrowsTypeErrorOnInvalidMessage(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the message as null instead of a string will cause a TypeError.
        $invalidMessage = null;

        /** @phpstan-ignore-next-line */
        JsonResponder::error($invalidMessage);
    }

    /**
     * Test that info() throws TypeError when message is not a string.
     *
     * @return void
     */
    public function testInfoThrowsTypeErrorOnInvalidMessage(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the message as an array instead of a string will cause a TypeError.
        $invalidMessage = [];

        /** @phpstan-ignore-next-line */
        JsonResponder::info($invalidMessage);
    }

    /**
     * Test that withData() throws TypeError when argument is not an array.
     *
     * @return void
     */
    public function testWithDataThrowsTypeErrorOnNonArray(): void
    {
        $this->expectException(\TypeError::class);

        $responder = JsonResponder::success('msg');

        // Sending the data as a string instead of an array will cause a TypeError.
        $invalidData = 'not-an-array';

        /** @phpstan-ignore-next-line */
        $responder->withData($invalidData);
    }

    /**
     * Test that withPayload() throws TypeError when argument is not an array.
     *
     * @return void
     */
    public function testWithPayloadThrowsTypeErrorOnNonArray(): void
    {
        $this->expectException(\TypeError::class);

        $responder = JsonResponder::success('msg');

        // Sending the payload as a string instead of an array will cause a TypeError.
        $invalidData = 'string-instead-of-array';

        /** @phpstan-ignore-next-line */
        $responder->withPayload($invalidData);
    }

    /**
     * Test that withTotalPages() throws TypeError when argument is not an int.
     *
     * @return void
     */
    public function testWithTotalPagesThrowsTypeErrorOnNonInt(): void
    {
        $this->expectException(\TypeError::class);

        $responder = JsonResponder::success('msg');

        // Sending the total as a string instead of an int will cause a TypeError.
        $invalidTotal = '10';

        /** @phpstan-ignore-next-line */
        $responder->withTotalPages($invalidTotal);
    }

    /**
     * Test that withType() throws TypeError when argument is not a string.
     *
     * @return void
     */
    public function testWithTypeThrowsTypeErrorOnNonString(): void
    {
        $this->expectException(\TypeError::class);

        $responder = JsonResponder::success('msg');

        // Sending the type as an int instead of a string will cause a TypeError.
        $invalidType = 123;

        /** @phpstan-ignore-next-line */
        $responder->withType($invalidType);
    }

    /**
     * Test that withHttpStatus() throws TypeError when argument is not an int.
     *
     * @return void
     */
    public function testWithHttpStatusThrowsTypeErrorOnNonInt(): void
    {
        $this->expectException(\TypeError::class);

        $responder = JsonResponder::success('msg');

        // Sending the status as a string instead of an int will cause a TypeError.
        $invalidStatus = '200';

        /** @phpstan-ignore-next-line */
        $responder->withHttpStatus($invalidStatus);
    }
}
