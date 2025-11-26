<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use App\Utils\JwtService;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/**
 * Class JwtServiceTest
 *
 * Unit tests for the JwtService class.
 *
 * Covers constructor behavior, token creation, decoding, verification,
 * refresh logic, and refresh threshold handling.
 *
 * Each test is self-contained with controlled timestamps.
 *
 * @package Tests\Unit\Utils
 */
class JwtServiceUnitTest extends TestCase
{
    /**
     * Test that constructor throws RuntimeException when no secret is set
     *
     * - Temporarily unsets JWT_SECRET environment variable
     * - Expects a RuntimeException to be thrown with a specific message
     * - Restores environment variable after test
     * 
     * @return void
     */
    public function testConstructorThrowsWhenNoSecret(): void
    {
        $original = getenv('JWT_SECRET') ?: null;

        // Temporarily remove JWT_SECRET
        putenv('JWT_SECRET'); // unset by emptying

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('JWT_SECRET is not set');
            new JwtService(null); // Should throw due to missing secret
        } finally {
            // Restore environment variable
            if ($original !== null) {
                putenv("JWT_SECRET=$original");
            } else {
                putenv('JWT_SECRET'); // unset
            }
        }
    }


    /**
     * Test that create() builds JWT with provided claims and correct standard timestamps
     *
     * - Creates a token with custom claims ('sub', 'role')
     * - Sets a controlled "now" timestamp for predictable iat, nbf, exp
     * - Decodes token to verify both custom and standard claims
     * 
     * @return void
     */
    public function testCreateBuildsJwtWithClaimsAndTimestamps(): void
    {
        $secret = 'test-secret';
        $expire = 3600;
        $now    = time() - 1;

        $svc   = new JwtService($secret, 'HS256', $expire, 600);
        $token = $svc->create(['sub' => '123', 'role' => 'user'], $now);

        $payload = $svc->decodeStrict($token);

        // Assert custom claims are preserved
        $this->assertSame('123', $payload['sub']);
        $this->assertSame('user', $payload['role']);

        // Assert standard JWT claims
        $this->assertSame($now, $payload['iat']);        // Issued at matches controlled time
        $this->assertSame($now, $payload['nbf']);        // Not valid before matches controlled time
        $this->assertSame($now + $expire, $payload['exp']); // Expiration calculated correctly
    }

    /**
     * Test that decodeStrict() throws when token signature does not match
     *
     * - Creates a token with one secret
     * - Attempts to decode with a different secret
     * - Expects an exception due to invalid signature
     * 
     * @return void
     */
    public function testDecodeStrictThrowsOnBadSignature(): void
    {
        $now = time() - 1;

        $issuer = new JwtService('issuer-secret', 'HS256', 3600, 600);
        $token  = $issuer->create(['sub' => 'abc'], $now);

        $verifier = new JwtService('verifier-secret', 'HS256', 3600, 600);

        $this->expectException(Throwable::class);
        $verifier->decodeStrict($token); // Should fail due to different secret
    }

    /**
     * Test that verify() returns null when token is missing or empty
     *
     * - Handles null or empty string gracefully
     * - Ensures no exception is thrown
     * - Returns null as expected
     * 
     * @return void
     */
    public function testVerifyReturnsNullWhenTokenMissing(): void
    {
        $svc = new JwtService('secret');
        $this->assertNull($svc->verify(null));
        $this->assertNull($svc->verify(''));
    }

    /**
     * Test that verify() returns payload for valid token
     *
     * - Creates a valid JWT with custom claim
     * - Calls verify() which internally decodes token
     * - Ensures returned payload is an array and custom claim is preserved
     * 
     * @return void
     */
    public function testVerifyReturnsPayloadForValidToken(): void
    {
        $svc   = new JwtService('secret', 'HS256', 3600, 600);
        $now   = time() - 1;
        $token = $svc->create(['uid' => 42], $now);

        $payload = $svc->verify($token);

        $this->assertIsArray($payload);
        $this->assertSame(42, $payload['uid']); // Custom claim preserved
    }

    /**
     * Test that verify() returns null for invalid token
     *
     * - Passes a malformed string
     * - Ensures verify() handles it gracefully without exception
     * 
     * @return void
     */
    public function testVerifyReturnsNullForInvalidToken(): void
    {
        $svc = new JwtService('secret');
        $this->assertNull($svc->verify('not-a-jwt'));
    }

    /**
     * Test shouldRefresh() returns true when token is close to expiration
     *
     * - Tests payload within threshold and outside threshold
     * - Ensures refresh decision logic is correct
     * 
     * @return void
     */
    public function testShouldRefreshTrueWhenCloseToExpiry(): void
    {
        $threshold = 600;
        $svc       = new JwtService('secret', 'HS256', 3600, $threshold);
        $now       = time();

        $payloadSoon = ['exp' => $now + 300];  // Within threshold -> should refresh
        $payloadFar  = ['exp' => $now + 10_000]; // Outside threshold -> no refresh

        $this->assertTrue($svc->shouldRefresh($payloadSoon, $now));
        $this->assertFalse($svc->shouldRefresh($payloadFar, $now));
    }

    /**
     * Test shouldRefresh() returns true when payload has no exp claim
     *
     * - Covers edge case where exp is missing
     * - Should always suggest refresh
     * 
     * @return void
     */
    public function testShouldRefreshTrueWhenNoExpInPayload(): void
    {
        $svc = new JwtService('secret', 'HS256', 3600, 600);
        $now = time();

        $this->assertTrue($svc->shouldRefresh([], $now));
    }

    /**
     * Test refresh() reissues a new token while preserving custom claims
     *
     * - Creates an old token with custom claims
     * - Decodes old token
     * - Refreshes token, generating new iat, nbf, exp
     * - Ensures custom claims are preserved
     * - Verifies timestamps updated correctly
     * - Confirms new token string is different from old token
     * 
     * @return void
     */
    public function testRefreshReissuesNewTokenAndPreservesCustomClaims(): void
    {
        $secret   = 'secret';
        $expire   = 7200;
        $nowOld   = time() - 1000;
        $nowNew   = time() - 1;

        $svc      = new JwtService($secret, 'HS256', $expire, 600);

        // Create old token
        $oldToken   = $svc->create(['name' => 'alice', 'role' => 'user'], $nowOld);
        $oldPayload = $svc->decodeStrict($oldToken);

        // Refresh token
        $newToken   = $svc->refresh($oldPayload, $nowNew);
        $newPayload = $svc->decodeStrict($newToken);

        // Custom claims preserved
        $this->assertSame('alice', $newPayload['name']);
        $this->assertSame('user',  $newPayload['role']);

        // Standard claims updated
        $this->assertSame($nowNew, $newPayload['iat']);
        $this->assertSame($nowNew, $newPayload['nbf']);
        $this->assertSame($nowNew + $expire, $newPayload['exp']);

        // Ensure new token differs from old token
        $this->assertNotSame($oldToken, $newToken);
    }
}
