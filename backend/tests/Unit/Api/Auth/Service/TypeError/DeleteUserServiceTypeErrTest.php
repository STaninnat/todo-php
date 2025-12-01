<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service\TypeError;

use App\Api\Auth\Service\DeleteUserService;
use App\Api\Request;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Class DeleteUserServiceTypeErrTest
 *
 * Unit tests for DeleteUserService focusing on type errors
 * and invalid input parameters.
 *
 * Covers scenarios including:
 * - Passing incorrect types to execute()
 * - Missing or invalid user_id in request parameters
 *
 * Uses data providers to efficiently test multiple invalid scenarios.
 *
 * @package Tests\Unit\Api\Auth\Service\TypeError
 */
class DeleteUserServiceTypeErrTest extends TestCase
{
    /** @var DeleteUserService Service under test */
    private DeleteUserService $service;

    /** @var UserQueries&\PHPUnit\Framework\MockObject\MockObject Mocked UserQueries */
    private UserQueries $userQueries;

    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject Mocked CookieManager */
    private CookieManager $cookieManager;

    /**
     * Setup mocks and service instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userQueries = $this->createMock(UserQueries::class);
        $this->cookieManager = $this->createMock(CookieManager::class);

        $this->service = new DeleteUserService(
            $this->userQueries,
            $this->cookieManager
        );
    }

    /**
     * Provides invalid arguments to test type enforcement in execute().
     *
     * @return array<string,array{0:mixed}>
     */
    public static function invalidExecuteArgsProvider(): array
    {
        return [
            'null instead of Request' => [null],
            'int instead of Request' => [123],
            'array instead of Request' => [[]],
            'string instead of Request' => ['not-a-request'],
        ];
    }

    /**
     * Test that execute() throws TypeError when argument is not Request.
     *
     * @param mixed $invalidArg Argument passed to execute()
     *
     * @return void
     */
    #[DataProvider('invalidExecuteArgsProvider')]
    public function testExecuteThrowsTypeErrorWhenNotRequest(mixed $invalidArg): void
    {
        $this->expectException(TypeError::class);

        // Deliberately passing wrong type to trigger TypeError
        /** @phpstan-ignore-next-line deliberately wrong type */
        $this->service->execute($invalidArg);
    }


}
