<?php

declare(strict_types=1);

namespace Tests\Unit\DB\TypeError;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\DB\Database;
use TypeError;

/**
 * Class DatabaseTypeErrTest
 *
 * Unit tests for Database class to ensure strict typing enforcement.
 * Tests that TypeError is thrown when constructor arguments are of invalid types.
 *
 * @package Tests\Unit\DB\TypeError
 */
class DatabaseTypeErrTest extends TestCase
{
    /**
     * Test Database constructor throws TypeError on invalid argument types.
     *
     * @param string $param The name of the parameter being tested ('dsn', 'user', or 'pass').
     * @param mixed $value The invalid value to pass.
     *
     * @return void
     */
    #[DataProvider('invalidTypeProvider')]
    public function testConstructorInvalidTypes(string $param, $value): void
    {
        $this->expectException(TypeError::class);

        $dsn = 'mysql:host=localhost;dbname=test';
        $user = 'user';
        $pass = 'pass';

        // Replace the corresponding param with invalid value
        if ($param === 'dsn') {
            $dsn = $value;
        }
        if ($param === 'user') {
            $user = $value;
        }
        if ($param === 'pass') {
            $pass = $value;
        }

        new Database($dsn, $user, $pass);
    }

    /**
     * Provides invalid types for Database constructor arguments.
     *
     * Each entry is an array of [parameter_name, invalid_value].
     *
     * @return array<int, array{0:string,1:mixed}>
     */
    public static function invalidTypeProvider(): array
    {
        return [
            ['dsn', 123],
            ['dsn', 12.34],
            ['dsn', true],
            ['dsn', []],
            ['dsn', new \stdClass()],
            ['user', 123],
            ['user', 12.34],
            ['user', true],
            ['user', []],
            ['user', new \stdClass()],
            ['pass', 123],
            ['pass', 12.34],
            ['pass', true],
            ['pass', []],
            ['pass', new \stdClass()],
        ];
    }
}
