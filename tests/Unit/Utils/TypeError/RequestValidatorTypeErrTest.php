<?php

declare(strict_types=1);

namespace Tests\Unit\Utils\TypeError;

use App\Api\Request;
use App\Utils\RequestValidator;
use PHPUnit\Framework\TestCase;
use TypeError;

class RequestValidatorTypeErrTest extends TestCase
{
    // -------- getIntParam --------
    public function testGetIntParamThrowsTypeErrorWhenRequestIsNull(): void
    {
        $this->expectException(TypeError::class);
        RequestValidator::getIntParam(null, 'id', 'error');
    }

    public function testGetIntParamThrowsTypeErrorWhenKeyIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getIntParam($req, 123, 'error');
    }

    public function testGetIntParamThrowsTypeErrorWhenErrorMsgIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getIntParam($req, 'id', 123);
    }

    // -------- getStringParam --------
    public function testGetStringParamThrowsTypeErrorWhenRequestIsNull(): void
    {
        $this->expectException(TypeError::class);
        RequestValidator::getStringParam(null, 'name', 'error');
    }

    public function testGetStringParamThrowsTypeErrorWhenKeyIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getStringParam($req, 123, 'error');
    }

    public function testGetStringParamThrowsTypeErrorWhenErrorMsgIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getStringParam($req, 'name', 456);
    }

    // -------- getEmailParam --------
    public function testGetEmailParamThrowsTypeErrorWhenRequestIsNull(): void
    {
        $this->expectException(TypeError::class);
        RequestValidator::getEmailParam(null, 'email', 'error');
    }

    public function testGetEmailParamThrowsTypeErrorWhenKeyIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getEmailParam($req, 123, 'error');
    }

    public function testGetEmailParamThrowsTypeErrorWhenErrorMsgIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getEmailParam($req, 'email', []);
    }

    // -------- getBoolParam --------
    public function testGetBoolParamThrowsTypeErrorWhenRequestIsNull(): void
    {
        $this->expectException(TypeError::class);
        RequestValidator::getBoolParam(null, 'flag', 'error');
    }

    public function testGetBoolParamThrowsTypeErrorWhenKeyIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getBoolParam($req, 123, 'error');
    }

    public function testGetBoolParamThrowsTypeErrorWhenErrorMsgIsNotString(): void
    {
        $req = $this->createMock(Request::class);
        $this->expectException(TypeError::class);
        RequestValidator::getBoolParam($req, 'flag', null);
    }

    // -------- ensureSuccess --------
    public function testEnsureSuccessThrowsTypeErrorWhenResultIsNotObject(): void
    {
        $this->expectException(TypeError::class);
        RequestValidator::ensureSuccess(null, 'delete', false);
    }

    public function testEnsureSuccessThrowsTypeErrorWhenActionIsNotString(): void
    {
        $result = new \stdClass();
        $this->expectException(TypeError::class);
        RequestValidator::ensureSuccess($result, 123);
    }
}
