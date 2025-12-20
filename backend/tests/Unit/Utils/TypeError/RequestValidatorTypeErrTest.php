<?php

declare(strict_types=1);

namespace Tests\Unit\Utils\TypeError;

use App\Api\Request;
use App\Utils\RequestValidator;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Class RequestValidatorTypeErrTest
 *
 * Unit tests ensuring that RequestValidator correctly throws
 * TypeError when parameters of incorrect types are passed.
 *
 * Covers type errors for:
 * - getInt
 * - getString
 * - getEmail
 * - getBool
 * - ensureSuccess
 *
 * @package Tests\Unit\Utils\TypeError
 */
class RequestValidatorTypeErrTest extends TestCase
{
    // -------- getInt --------

    /**
     * Ensure getInt throws when request object is null.
     *
     * @return void
     */
    public function testGetIntThrowsTypeErrorWhenRequestIsNull(): void
    {
        $this->expectException(TypeError::class);
        RequestValidator::getInt(null, 'id', 'error');
    }

    /**
     * Ensure getInt throws when key is not a string.
     *
     * @return void
     */
    public function testGetIntThrowsTypeErrorWhenKeyIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getInt($req, 123, 'error');
    }

    /**
     * Ensure getInt throws when error message is not a string.
     *
     * @return void
     */
    public function testGetIntThrowsTypeErrorWhenErrorMsgIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getInt($req, 'id', 123);
    }

    // -------- getString --------

    /**
     * Ensure getString throws when request object is null.
     *
     * @return void
     */
    public function testGetStringThrowsTypeErrorWhenRequestIsNull(): void
    {
        $this->expectException(TypeError::class);
        RequestValidator::getString(null, 'name', 'error');
    }

    /**
     * Ensure getString throws when key is not a string.
     *
     * @return void
     */
    public function testGetStringThrowsTypeErrorWhenKeyIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getString($req, 123, 'error');
    }

    /**
     * Ensure getString throws when error message is not a string.
     *
     * @return void
     */
    public function testGetStringThrowsTypeErrorWhenErrorMsgIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getString($req, 'name', 456);
    }

    // -------- getEmail --------

    /**
     * Ensure getEmail throws when request object is null.
     *
     * @return void
     */
    public function testGetEmailThrowsTypeErrorWhenRequestIsNull(): void
    {
        $this->expectException(TypeError::class);
        RequestValidator::getEmail(null, 'email', 'error');
    }

    /**
     * Ensure getEmail throws when key is not a string.
     *
     * @return void
     */
    public function testGetEmailThrowsTypeErrorWhenKeyIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getEmail($req, 123, 'error');
    }

    /**
     * Ensure getEmail throws when error message is not a string.
     *
     * @return void
     */
    public function testGetEmailThrowsTypeErrorWhenErrorMsgIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getEmail($req, 'email', []);
    }

    // -------- getBool --------

    /**
     * Ensure getBool throws when request object is null.
     *
     * @return void
     */
    public function testGetBoolThrowsTypeErrorWhenRequestIsNull(): void
    {
        $this->expectException(TypeError::class);
        RequestValidator::getBool(null, 'flag', 'error');
    }

    /**
     * Ensure getBool throws when key is not a string.
     *
     * @return void
     */
    public function testGetBoolThrowsTypeErrorWhenKeyIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getBool($req, 123, 'error');
    }

    /**
     * Ensure getBool throws when error message is not a string.
     *
     * @return void
     */
    public function testGetBoolThrowsTypeErrorWhenErrorMsgIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getBool($req, 'flag', null);
    }

    // -------- getArray --------

    /**
     * Ensure getArray throws when request object is null.
     *
     * @return void
     */
    public function testGetArrayThrowsTypeErrorWhenRequestIsNull(): void
    {
        $this->expectException(TypeError::class);
        RequestValidator::getArray(null, 'list', 'error');
    }

    /**
     * Ensure getArray throws when key is not a string.
     *
     * @return void
     */
    public function testGetArrayThrowsTypeErrorWhenKeyIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getArray($req, 123, 'error');
    }

    // -------- getAuthUserId --------

    /**
     * Ensure getAuthUserId throws when request object is null.
     *
     * @return void
     */
    public function testGetAuthUserIdThrowsTypeErrorWhenRequestIsNull(): void
    {
        $this->expectException(TypeError::class);
        RequestValidator::getAuthUserId(null);
    }

    // -------- ensureSuccess --------

    /**
     * Ensure ensureSuccess throws when result value is not an object.
     *
     * @return void
     */
    public function testEnsureSuccessThrowsTypeErrorWhenResultIsNotObject(): void
    {
        $this->expectException(TypeError::class);
        RequestValidator::ensureSuccess(null, 'delete', false);
    }

    /**
     * Ensure ensureSuccess throws when action argument is not a string.
     *
     * @return void
     */
    public function testEnsureSuccessThrowsTypeErrorWhenActionIsNotString(): void
    {
        $result = new \stdClass();
        $this->expectException(TypeError::class);
        RequestValidator::ensureSuccess($result, 123);
    }
}
