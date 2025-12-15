<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Class CookieManager
 * 
 * Manages cookies related to authentication, such as access tokens.
 * 
 * This class provides methods to get, set, and clear the 'access_token' cookie.
 * It can work with any implementation of CookieStorageInterface, defaulting to NativeCookieStorage.
 * 
 * @package App\Utils
 */
class CookieManager
{
    /**
     * @var CookieStorageInterface The storage implementation used to access cookie values.
     */
    private CookieStorageInterface $storage;

    private ?string $lastSetCookieName = null;

    /**
     * Constructor.
     *
     * @param CookieStorageInterface|null $storage Optional. Custom storage implementation. Defaults to NativeCookieStorage.
     */
    public function __construct(?CookieStorageInterface $storage = null)
    {
        $this->storage = $storage ?? new NativeCookieStorage();
    }

    /**
     * Get the value of the 'access_token' cookie.
     *
     * @return string|null The access token if set, otherwise null.
     */
    public function getAccessToken(): ?string
    {
        return $this->storage->get('access_token');
    }

    /**
     * Set the 'access_token' cookie with a specified expiration time.
     *
     * @param string $token The access token value to set.
     * @param int $expires The Unix timestamp when the cookie should expire.
     */
    public function setAccessToken(string $token, int $expires): void
    {
        $this->lastSetCookieName = 'access_token';
        $this->storage->set('access_token', $token, $expires);
    }

    /**
     * Clear the 'access_token' cookie by setting it with an expired timestamp.
     */
    public function clearAccessToken(): void
    {
        $this->lastSetCookieName = 'access_token';
        $this->storage->delete('access_token');
    }

    /**
     * Set the 'refresh_token' cookie.
     *
     * @param string $token The refresh token
     * @param int $expires The expiration timestamp
     */
    public function setRefreshToken(string $token, int $expires): void
    {
        $this->lastSetCookieName = 'refresh_token';
        $this->storage->set('refresh_token', $token, $expires);
    }

    /**
     * Get the 'refresh_token' cookie.
     *
     * @return string|null
     */
    public function getRefreshToken(): ?string
    {
        return $this->storage->get('refresh_token');
    }

    /**
     * Clear the 'refresh_token' cookie.
     */
    public function clearRefreshToken(): void
    {
        $this->lastSetCookieName = 'refresh_token';
        $this->storage->delete('refresh_token');
    }

    // for test
    public function getLastSetCookieName(): ?string
    {
        return $this->lastSetCookieName;
    }
}
