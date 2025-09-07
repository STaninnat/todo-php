<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use App\Utils\JwtService;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class JwtServiceTestTypeErrTest extends TestCase
{
    /**
     * Test constructor throws TypeError for invalid expire type
     */
    public function testConstructorThrowsTypeErrorOnInvalidExpire(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the expire as a string instead of an int will cause a TypeError.
        new JwtService('secret', 'HS256', 'not-an-int');
    }

    /**
     * Test constructor throws TypeError for invalid refreshThreshold type
     */
    public function testConstructorThrowsTypeErrorOnInvalidRefreshThreshold(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the refreshThreshold as a string instead of an int will cause a TypeError.
        new JwtService('secret', 'HS256', 3600, 'not-an-int');
    }

    /**
     * Test create() throws TypeError if claims is not array
     */
    public function testCreateThrowsTypeErrorOnInvalidClaims(): void
    {
        $svc = new \App\Utils\JwtService('secret');

        $this->expectException(\TypeError::class);

        // Sending the claims as a string instead of an array will cause a TypeError.
        $svc->create('not-an-array');
    }

    /**
     * Test decodeStrict() throws TypeError if token is not string
     */
    public function testDecodeStrictThrowsTypeErrorOnInvalidToken(): void
    {
        $svc = new \App\Utils\JwtService('secret');

        $this->expectException(\TypeError::class);

        // Sending the token as an int instead of a string will cause a TypeError.
        $svc->decodeStrict(123);
    }

    /**
     * Test verify() throws TypeError if token is not string|null
     */
    public function testVerifyThrowsTypeErrorOnInvalidToken(): void
    {
        $svc = new \App\Utils\JwtService('secret');

        $this->expectException(\TypeError::class);

        // Sending the token as an array instead of a string|null will cause a TypeError.
        $svc->verify([]);
    }

    /**
     * Test shouldRefresh() throws TypeError if payload is not array
     */
    public function testShouldRefreshThrowsTypeErrorOnInvalidPayload(): void
    {
        $svc = new \App\Utils\JwtService('secret');

        $this->expectException(\TypeError::class);

        // Sending the payload as a string instead of an array will cause a TypeError.
        $svc->shouldRefresh('not-an-array');
    }

    /**
     * Test refresh() throws TypeError if payload is not array
     */
    public function testRefreshThrowsTypeErrorOnInvalidPayload(): void
    {
        $svc = new \App\Utils\JwtService('secret');

        $this->expectException(\TypeError::class);

        // Sending the token as a string instead of an array will cause a TypeError.
        $svc->refresh('not-an-array');
    }
}
