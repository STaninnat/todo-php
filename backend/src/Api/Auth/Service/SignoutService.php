<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\Utils\CookieManager;

/**
 * Class SignoutService
 *
 * Service responsible for handling user sign-out.
 *
 * This service:
 * - Clears the authentication token stored in cookies.
 * - Effectively invalidates the current user session.
 *
 * @package App\Api\Auth\Service
 */
class SignoutService
{
    private CookieManager $cookieManager;

    /**
     * Constructor
     *
     * @param CookieManager $cookieManager Utility for managing authentication cookies.
     */
    public function __construct(CookieManager $cookieManager)
    {
        $this->cookieManager = $cookieManager;
    }

    /**
     * Execute the sign-out process.
     *
     * Process:
     * - Clear the access token from cookies to log the user out.
     *
     * @return void
     */
    public function execute(): void
    {
        // Remove access token from cookies
        $this->cookieManager->clearAccessToken();
    }
}
