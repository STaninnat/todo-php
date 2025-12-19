<?php

declare(strict_types=1);

namespace App\Api\Exceptions;

use RuntimeException;

/**
 * Class UnauthorizedException
 *
 * Exception thrown when authentication fails.
 * Should result in a 401 HTTP response.
 *
 * @package App\Api\Exceptions
 */
class UnauthorizedException extends RuntimeException
{
    protected $code = 401;
}
