<?php

declare(strict_types=1);

namespace App\DB;

use RuntimeException;

/**
 * Class DatabaseConfigException
 * 
 * Exception thrown when there is an error with the database configuration,
 * such as missing environment variables or invalid connection parameters.
 * 
 * @package App\DB
 */
final class DatabaseConfigException extends RuntimeException
{
}

