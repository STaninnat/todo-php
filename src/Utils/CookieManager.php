<?php

namespace App\Utils;

/**
 * Manages cookies related to authentication, such as access tokens.
 * 
 * This class provides methods to get, set, and clear the 'access_token' cookie.
 * It can work with any implementation of CookieStorageInterface, defaulting to NativeCookieStorage.
 */
class CookieManager
{
    /**
     * @var CookieStorageInterface The storage implementation used to access cookie values.
     */
    private CookieStorageInterface $storage;

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
        $this->setCookie('access_token', $token, $expires);
    }

    /**
     * Clear the 'access_token' cookie by setting it with an expired timestamp.
     */
    public function clearAccessToken(): void
    {
        $this->setCookie('access_token', '', time() - 3600);
    }

    /**
     * Internal method to set a cookie with secure defaults.
     *
     * @param string $name The name of the cookie.
     * @param string $value The value to store in the cookie.
     * @param int $expires The Unix timestamp when the cookie should expire.
     */
    protected function setCookie(string $name, string $value, int $expires): void
    {
        setcookie($name, $value, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }
}
