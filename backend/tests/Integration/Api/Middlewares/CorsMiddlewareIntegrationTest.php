<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Middlewares;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

/**
 * Class CorsMiddlewareIntegrationTest
 *
 * Integration tests for {@see CorsMiddleware}.
 *
 * Verifies that the deployed application stack (Nginx + PHP-FPM) correctly
 * processes CORS headers in real HTTP requests.
 *
 * Test scenarios include:
 * - Confirms Nginx/PHP communication for header injection
 * - Validates preflight (OPTIONS) request handling in the full stack
 *
 * @package Tests\Integration\Api\Middlewares
 */
class CorsMiddlewareIntegrationTest extends TestCase
{
    /**
     * Guzzle HTTP client for making requests to the API.
     *
     * @var Client
     */
    private Client $client;

    /**
     * Sets up the test environment.
     *
     * Initializes the Guzzle HTTP client with the base URI set to the nginx service
     * name (when running in Docker) and disables HTTP errors for more flexible
     * response handling.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // When running inside Docker network, we talk to the nginx service name directly.
        // Nginx exposes port 80 internally.
        $this->client = new Client([
            'base_uri' => "http://nginx",
            'http_errors' => false,
        ]);
    }

    /**
     * Tests that CORS headers are present on API requests.
     *
     * @return void
     */
    public function testCorsHeadersArePresentOnApiRequest(): void
    {
        $response = $this->client->request('GET', '/v1/users/me', [
            'headers' => [
                'Origin' => 'http://localhost:5173',
            ]
        ]);

        $this->assertTrue($response->hasHeader('Access-Control-Allow-Origin'));
        $this->assertTrue($response->hasHeader('Access-Control-Allow-Credentials'));

        $origin = $response->getHeaderLine('Access-Control-Allow-Origin');
        $this->assertStringContainsString('localhost:5173', $origin);
    }

    /**
     * Tests that preflight OPTIONS requests are handled correctly.
     *
     * @return void
     */
    public function testPreflightOptionsRequest(): void
    {
        $response = $this->client->request('OPTIONS', '/v1/users/me', [
            'headers' => [
                'Origin' => 'http://localhost:5173',
                'Access-Control-Request-Method' => 'GET'
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Access-Control-Allow-Methods'));
        $this->assertTrue($response->hasHeader('Access-Control-Max-Age'));
    }
}
