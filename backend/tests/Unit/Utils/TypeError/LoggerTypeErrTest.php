<?php

declare(strict_types=1);

namespace Tests\Unit\Utils\TypeError;

use App\Utils\Logger;
use App\Utils\NativeFileSystem;
use App\Utils\SystemClock;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Class LoggerTypeErrTest
 *
 * Unit tests for Logger to verify strict type enforcement.
 *
 * Ensures that passing invalid types to Logger methods or constructor
 * triggers TypeError exceptions.
 *
 * @package Tests\Unit\Utils\TypeError
 */
class LoggerTypeErrTest extends TestCase
{
    /**
     * Ensure passing an int to setDebug() triggers TypeError.
     *
     * @return void
     */
    public function testSetDebugWithIntThrowsTypeError(): void
    {
        // Arrange: create Logger instance
        $logger = new Logger(
            new NativeFileSystem(),
            new SystemClock()
        );

        // Assert: expect a TypeError for invalid debug type
        $this->expectException(TypeError::class);

        // Act: call setDebug() with int instead of bool
        $logger->setDebug(123);
    }

    /**
     * Ensure constructor throws TypeError when passed invalid FileSystemInterface.
     *
     * @return void
     */
    public function testConstructorWithInvalidFsThrowsTypeError(): void
    {
        // Assert: expect TypeError when constructor receives wrong type
        $this->expectException(TypeError::class);

        // Act: pass string instead of FileSystemInterface
        new Logger("not-an-fs");
    }
}
