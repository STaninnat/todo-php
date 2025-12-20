<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use App\Api\Request;
use App\Utils\RequestValidator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class DummyResult
 *
 * A simple dummy result class to simulate service responses
 * for testing RequestValidator::ensureSuccess.
 *
 * @package Tests\Unit\Utils
 */
class DummyResult
{
    public bool $success;

    /** @var list<string>|array<string, mixed>|null */
    public ?array $error;

    private bool $changed;

    /**
     * @var list<string>|null
     */
    public $data;

    /**
     * Constructor.
     *
     * @param bool                  $success Indicates whether the operation succeeded.
     * @param bool                  $changed Indicates whether the result was changed.
     * @param list<string>|null     $error   Optional error details.
     */
    public function __construct(bool $success, bool $changed, ?array $error = null)
    {
        $this->success = $success;
        $this->changed = $changed;
        $this->error = $error;
        $this->data = $success ? ['dummy'] : null;
    }

    /**
     * Check if the result was changed.
     *
     * @return bool
     */
    public function isChanged(): bool
    {
        return $this->changed;
    }
}

/**
 * Class RequestValidatorTest
 *
 * Unit tests for the RequestValidator utility class.
 *
 * This suite verifies input validation logic for:
 * - Integer parameters
 * - Boolean parameters
 * - String parameters
 * - Email parameters
 * - EnsureSuccess method behavior
 *
 * @package Tests\Unit\Utils
 */
class RequestValidatorUnitTest extends TestCase
{
    /** @var Request&MockObject */
    private Request $req;

    /**
     * Set up the test environment before each test.
     *
     * Creates a mock request object for input simulation.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->req = $this->createMock(Request::class);
    }

    // -------------------------
    // getInt
    // -------------------------

    /**
     * Data provider for valid integer parameters.
     *
     * @return array<string, array{string, int}>
     */
    public static function intProvider(): array
    {
        return [
            'valid int' => ['123', 123],
            'zero' => ['0', 0],
        ];
    }

    /**
     * Test that getInt returns valid integers for numeric strings.
     *
     * @param string $input    Input value.
     * @param int    $expected Expected integer result.
     *
     * @return void
     */
    #[DataProvider('intProvider')]
    public function testGetIntValid(string $input, int $expected): void
    {
        $this->req->method('getParam')->willReturn($input);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->assertSame($expected, RequestValidator::getInt($this->req, 'id', 'Invalid id'));
    }

    /**
     * Data provider for invalid integer parameters.
     *
     * @return array<string, array{mixed}>
     */
    public static function intInvalidProvider(): array
    {
        return [
            'non digit' => ['abc'],
            'empty' => [null],
        ];
    }

    /**
     * Test that getInt throws an exception for invalid inputs.
     *
     * @param mixed $input Invalid input.
     *
     * @return void
     */
    #[DataProvider('intInvalidProvider')]
    public function testGetIntThrowsOnInvalid($input): void
    {
        $this->req->method('getParam')->willReturn($input);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->expectException(InvalidArgumentException::class);
        RequestValidator::getInt($this->req, 'id', 'Invalid id');
    }

    // -------------------------
    // getBool
    // -------------------------

    /**
     * Data provider for boolean parameters.
     *
     * @return array<string, array{string, bool}>
     */
    public static function boolProvider(): array
    {
        return [
            'true string' => ['1', true],
            'false string' => ['0', false],
            'nondigit' => ['yes', false],
        ];
    }

    /**
     * Test that getBool correctly parses boolean-like values.
     *
     * @param string $input    Input value.
     * @param bool   $expected Expected boolean result.
     *
     * @return void
     */
    #[DataProvider('boolProvider')]
    public function testGetBool(string $input, bool $expected): void
    {
        $this->req->method('getParam')->willReturn($input);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->assertSame($expected, RequestValidator::getBool($this->req, 'flag', 'Invalid flag', true));
    }

    /**
     * Test that getBool throws an exception when value is missing.
     *
     * @return void
     */
    public function testGetBoolThrowsOnMissing(): void
    {
        $this->req->method('getParam')->willReturn(null);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->expectException(InvalidArgumentException::class);
        RequestValidator::getBool($this->req, 'flag', 'Invalid flag');
    }

    // -------------------------
    // getString
    // -------------------------

    /**
     * Data provider for valid string parameters.
     *
     * @return array<string, array{string, string}>
     */
    public static function stringProvider(): array
    {
        return [
            'normal trimmed' => [' hello ', 'hello'],
            'with tags' => ['<b>world</b>', 'world'],
        ];
    }

    /**
     * Test that getString sanitizes and returns valid strings.
     *
     * @param string $input    Input value.
     * @param string $expected Expected sanitized string.
     *
     * @return void
     */
    #[DataProvider('stringProvider')]
    public function testGetStringValid(string $input, string $expected): void
    {
        $this->req->method('getParam')->willReturn($input);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->assertSame($expected, RequestValidator::getString($this->req, 'name', 'Invalid name'));
    }

    /**
     * Data provider for invalid string parameters.
     *
     * @return array<string, array{mixed}>
     */
    public static function stringInvalidProvider(): array
    {
        return [
            'empty string' => [''],
            'null value' => [null],
            'only tags' => ['<i></i>'],
        ];
    }

    /**
     * Test that getString throws an exception for invalid strings.
     *
     * @param mixed $input Invalid input.
     *
     * @return void
     */
    #[DataProvider('stringInvalidProvider')]
    public function testGetStringThrowsOnInvalid($input): void
    {
        $this->req->method('getParam')->willReturn($input);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->expectException(InvalidArgumentException::class);
        RequestValidator::getString($this->req, 'name', 'Invalid name');
    }

    // -------------------------
    // getEmail
    // -------------------------

    /**
     * Data provider for valid email parameters.
     *
     * @return array<string, array{string, string}>
     */
    public static function emailProvider(): array
    {
        return [
            'normal email' => ['user@example.com', 'user@example.com'],
            'with spaces' => ['  test@foo.com ', 'test@foo.com'],
        ];
    }

    /**
     * Test that getEmail validates and trims email addresses.
     *
     * @param string $input    Input email.
     * @param string $expected Expected valid email.
     *
     * @return void
     */
    #[DataProvider('emailProvider')]
    public function testGetEmailValid(string $input, string $expected): void
    {
        $this->req->method('getParam')->willReturn($input);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->assertSame($expected, RequestValidator::getEmail($this->req, 'email', 'Invalid email'));
    }

    /**
     * Data provider for invalid email parameters.
     *
     * @return array<string, array{mixed}>
     */
    public static function emailInvalidProvider(): array
    {
        return [
            'missing at' => ['invalid.com'],
            'empty' => [''],
            'null' => [null],
        ];
    }

    /**
     * Test that getEmail throws an exception for invalid emails.
     *
     * @param mixed $input Invalid email input.
     *
     * @return void
     */
    #[DataProvider('emailInvalidProvider')]
    public function testGetEmailThrowsOnInvalid($input): void
    {
        $this->req->method('getParam')->willReturn($input);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->expectException(InvalidArgumentException::class);
        RequestValidator::getEmail($this->req, 'email', 'Invalid email');
    }

    // -------------------------
    // getArray
    // -------------------------

    /**
     * Data provider for valid array parameters.
     *
     * @return array<string, array{array<mixed>}>
     */
    public static function arrayProvider(): array
    {
        return [
            'simple array' => [['a', 'b']],
            'assoc array' => [['key' => 'value']],
            'empty array' => [[]],
        ];
    }

    /**
     * Test that getArray returns valid arrays.
     *
     * @param array<mixed> $input
     *
     * @return void
     */
    #[DataProvider('arrayProvider')]
    public function testGetArrayValid(array $input): void
    {
        $this->req->method('getParam')->willReturn($input);

        $this->assertSame($input, RequestValidator::getArray($this->req, 'list', 'Invalid list'));
    }

    /**
     * Test: getArray throws on invalid types.
     * 
     * @return void
     */
    public function testGetArrayThrowsOnInvalid(): void
    {
        $this->req->method('getParam')->willReturn('not-array');

        $this->expectException(InvalidArgumentException::class);
        RequestValidator::getArray($this->req, 'list', 'Invalid list');
    }

    // -------------------------
    // getAuthUserId
    // -------------------------

    /**
     * Test: getAuthUserId returns id when present.
     * 
     * @return void
     */
    public function testGetAuthUserIdValid(): void
    {
        $this->req->auth = ['id' => 'user-123'];

        $this->assertSame('user-123', RequestValidator::getAuthUserId($this->req));
    }

    /**
     * Test: getAuthUserId throws RuntimeException when missing.
     * 
     * @return void
     */
    public function testGetAuthUserIdThrowsOnMissing(): void
    {
        $this->req->auth = [];

        $this->expectException(RuntimeException::class);
        RequestValidator::getAuthUserId($this->req);
    }


    // -------------------------
    // ensureSuccess
    // -------------------------

    /**
     * Data provider for ensureSuccess test cases.
     *
     * @return array<string, array{bool, bool, class-string<\Throwable>|null}>
     */
    public static function ensureSuccessProvider(): array
    {
        return [
            'successful and changed' => [true, true, null],
            'failed with error' => [false, false, RuntimeException::class],
            'successful but no change' => [true, false, RuntimeException::class],
        ];
    }

    /**
     * Test that ensureSuccess behaves correctly depending on result state.
     *
     * @param bool        $success                              Whether the operation succeeded.
     * @param bool        $isChanged                            Whether the result was changed.
     * @param class-string<\Throwable>|null $expectedException  Expected exception class, if any.
     *
     * @return void
     */
    #[DataProvider('ensureSuccessProvider')]
    public function testEnsureSuccess(bool $success, bool $isChanged, ?string $expectedException): void
    {
        $result = new DummyResult(
            $success,
            $isChanged,
            $success ? null : ['Some error']    // list<string>
        );

        if ($expectedException) {
            $this->expectException($expectedException);
        }

        RequestValidator::ensureSuccess($result, 'testing');

        $this->assertInstanceOf(DummyResult::class, $result);
    }
}
