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
        $this->error   = $error;
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
    // getIntParam
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
            'zero'      => ['0', 0],
        ];
    }

    /**
     * Test that getIntParam returns valid integers for numeric strings.
     *
     * @param string $input    Input value.
     * @param int    $expected Expected integer result.
     *
     * @return void
     */
    #[DataProvider('intProvider')]
    public function testGetIntParamValid(string $input, int $expected): void
    {
        $this->req->method('getParam')->willReturn($input);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->assertSame($expected, RequestValidator::getIntParam($this->req, 'id', 'Invalid id'));
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
            'empty'     => [null],
        ];
    }

    /**
     * Test that getIntParam throws an exception for invalid inputs.
     *
     * @param mixed $input Invalid input.
     *
     * @return void
     */
    #[DataProvider('intInvalidProvider')]
    public function testGetIntParamThrowsOnInvalid($input): void
    {
        $this->req->method('getParam')->willReturn($input);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->expectException(InvalidArgumentException::class);
        RequestValidator::getIntParam($this->req, 'id', 'Invalid id');
    }

    // -------------------------
    // getBoolParam
    // -------------------------

    /**
     * Data provider for boolean parameters.
     *
     * @return array<string, array{string, bool}>
     */
    public static function boolProvider(): array
    {
        return [
            'true string'  => ['1', true],
            'false string' => ['0', false],
            'nondigit'     => ['yes', false],
        ];
    }

    /**
     * Test that getBoolParam correctly parses boolean-like values.
     *
     * @param string $input    Input value.
     * @param bool   $expected Expected boolean result.
     *
     * @return void
     */
    #[DataProvider('boolProvider')]
    public function testGetBoolParam(string $input, bool $expected): void
    {
        $this->req->method('getParam')->willReturn($input);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->assertSame($expected, RequestValidator::getBoolParam($this->req, 'flag', 'Invalid flag', true));
    }

    /**
     * Test that getBoolParam throws an exception when value is missing.
     *
     * @return void
     */
    public function testGetBoolParamThrowsOnMissing(): void
    {
        $this->req->method('getParam')->willReturn(null);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->expectException(InvalidArgumentException::class);
        RequestValidator::getBoolParam($this->req, 'flag', 'Invalid flag');
    }

    // -------------------------
    // getStringParam
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
            'with tags'      => ['<b>world</b>', 'world'],
        ];
    }

    /**
     * Test that getStringParam sanitizes and returns valid strings.
     *
     * @param string $input    Input value.
     * @param string $expected Expected sanitized string.
     *
     * @return void
     */
    #[DataProvider('stringProvider')]
    public function testGetStringParamValid(string $input, string $expected): void
    {
        $this->req->method('getParam')->willReturn($input);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->assertSame($expected, RequestValidator::getStringParam($this->req, 'name', 'Invalid name'));
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
            'null value'   => [null],
            'only tags'    => ['<i></i>'],
        ];
    }

    /**
     * Test that getStringParam throws an exception for invalid strings.
     *
     * @param mixed $input Invalid input.
     *
     * @return void
     */
    #[DataProvider('stringInvalidProvider')]
    public function testGetStringParamThrowsOnInvalid($input): void
    {
        $this->req->method('getParam')->willReturn($input);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->expectException(InvalidArgumentException::class);
        RequestValidator::getStringParam($this->req, 'name', 'Invalid name');
    }

    // -------------------------
    // getEmailParam
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
            'with spaces'  => ['  test@foo.com ', 'test@foo.com'],
        ];
    }

    /**
     * Test that getEmailParam validates and trims email addresses.
     *
     * @param string $input    Input email.
     * @param string $expected Expected valid email.
     *
     * @return void
     */
    #[DataProvider('emailProvider')]
    public function testGetEmailParamValid(string $input, string $expected): void
    {
        $this->req->method('getParam')->willReturn($input);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->assertSame($expected, RequestValidator::getEmailParam($this->req, 'email', 'Invalid email'));
    }

    /**
     * Data provider for invalid email parameters.
     *
     * @return array<string, array{mixed}>
     */
    public static function emailInvalidProvider(): array
    {
        return [
            'missing at'   => ['invalid.com'],
            'empty'        => [''],
            'null'         => [null],
        ];
    }

    /**
     * Test that getEmailParam throws an exception for invalid emails.
     *
     * @param mixed $input Invalid email input.
     *
     * @return void
     */
    #[DataProvider('emailInvalidProvider')]
    public function testGetEmailParamThrowsOnInvalid($input): void
    {
        $this->req->method('getParam')->willReturn($input);
        $this->req->method('getQuery')->willReturn(null);
        $this->req->body = [];

        $this->expectException(InvalidArgumentException::class);
        RequestValidator::getEmailParam($this->req, 'email', 'Invalid email');
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
            'successful and changed'    => [true, true, null],
            'failed with error'         => [false, false, RuntimeException::class],
            'successful but no change'  => [true, false, RuntimeException::class],
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
