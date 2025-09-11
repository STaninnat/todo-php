<?php

declare(strict_types=1);

namespace Tests\Unit\Utils\TypeError;

use App\Utils\JwtService;
use PHPUnit\Framework\TestCase;

/**
 * Class JwtServiceTestTypeErrTest
 *
 * Unit tests for JwtService to ensure strict typing enforcement.
 * Tests that TypeError is thrown when invalid types are passed to constructor or methods.
 *
 * @package Tests\Unit\Utils\TypeError
 */
class JwtServiceTestTypeErrTest extends TestCase
{
    /**
     * Test constructor throws TypeError when expire is not an int.
     *
     * @return void
     */
    public function testConstructorThrowsTypeErrorOnInvalidExpire(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the expire as a string instead of an int will cause a TypeError.
        /** @phpstan-ignore-next-line */
        new JwtService('secret', 'HS256', 'not-an-int');
    }

    /**
     * Test constructor throws TypeError when refreshThreshold is not an int.
     *
     * @return void
     */
    public function testConstructorThrowsTypeErrorOnInvalidRefreshThreshold(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the refreshThreshold as a string instead of an int will cause a TypeError.
        /** @phpstan-ignore-next-line */
        new JwtService('secret', 'HS256', 3600, 'not-an-int');
    }

    /**
     * Test create() throws TypeError when claims is not an array.
     *
     * @return void
     */
    public function testCreateThrowsTypeErrorOnInvalidClaims(): void
    {
        $svc = new JwtService('secret');

        $this->expectException(\TypeError::class);

        // Sending the claims as a string instead of an array will cause a TypeError.
        /** @phpstan-ignore-next-line */
        $svc->create('not-an-array');
    }

    /**
     * Test decodeStrict() throws TypeError when token is not a string.
     *
     * @return void
     */
    public function testDecodeStrictThrowsTypeErrorOnInvalidToken(): void
    {
        $svc = new JwtService('secret');

        $this->expectException(\TypeError::class);

        // Sending the token as an int instead of a string will cause a TypeError.
        /** @phpstan-ignore-next-line */
        $svc->decodeStrict(123);
    }

    /**
     * Test verify() throws TypeError when token is not string|null.
     *
     * @return void
     */
    public function testVerifyThrowsTypeErrorOnInvalidToken(): void
    {
        $svc = new JwtService('secret');

        $this->expectException(\TypeError::class);

        // Sending the token as an array instead of a string|null will cause a TypeError.
        /** @phpstan-ignore-next-line */
        $svc->verify([]);
    }

    /**
     * Test shouldRefresh() throws TypeError when payload is not an array.
     *
     * @return void
     */
    public function testShouldRefreshThrowsTypeErrorOnInvalidPayload(): void
    {
        $svc = new JwtService('secret');

        $this->expectException(\TypeError::class);

        // Sending the payload as a string instead of an array will cause a TypeError.
        /** @phpstan-ignore-next-line */
        $svc->shouldRefresh('not-an-array');
    }

    /**
     * Test refresh() throws TypeError when payload is not an array.
     *
     * @return void
     */
    public function testRefreshThrowsTypeErrorOnInvalidPayload(): void
    {
        $svc = new JwtService('secret');

        $this->expectException(\TypeError::class);

        // Sending the payload as a string instead of an array will cause a TypeError.
        /** @phpstan-ignore-next-line */
        $svc->refresh('not-an-array');
    }
}
