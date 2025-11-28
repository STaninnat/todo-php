<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\Logger;
use Tests\Unit\Utils\Fakes\FakeFileSystem;
use Tests\Unit\Utils\Fakes\FakeClock;

/**
 * Class LoggerTest
 *
 * Unit tests for the Logger class.
 *
 * Covers:
 * - Writing logs with different levels (INFO, WARNING, ERROR).
 * - Ensuring logs are written to correct files with correct formatting.
 * - Disabling logs when debug=false.
 * - Automatic cleanup of old log files.
 * - Behavior change when maxDays is updated.
 *
 * @package Tests\Unit\Utils
 */
class LoggerUnitTest extends TestCase
{
    /**
     * Test that an info log is correctly written with timestamp and level.
     *
     * @return void
     */
    /**
     * Test that an info log is correctly written to stdout.
     *
     * @return void
     */
    public function testWriteInfoLog(): void
    {
        // Arrange: fake file system + fixed clock
        $fs = new FakeFileSystem();
        $clock = new FakeClock(strtotime('2025-09-12 10:00:00'));
        $logger = new Logger($fs, $clock);

        // Act: write an info log
        $logger->info('Hello World');

        // Assert: log written to stdout
        $expectedFile = 'php://stdout';
        $this->assertTrue($fs->hasFile($expectedFile));
        $this->assertStringContainsString(
            '[2025-09-12 10:00:00][INFO] Hello World',
            $fs->getFileContent($expectedFile) ?? ''
        );
    }

    /**
     * Test that a warning log is written to stderr.
     *
     * @return void
     */
    public function testWriteWarningLog(): void
    {
        $fs = new FakeFileSystem();
        $clock = new FakeClock(strtotime('2025-09-12 11:30:00'));
        $logger = new Logger($fs, $clock);

        $logger->warning('Something odd');

        $expectedFile = 'php://stderr';
        $this->assertStringContainsString('[WARNING] Something odd', $fs->getFileContent($expectedFile) ?? '');
    }

    /**
     * Test that an error log is written to stderr.
     *
     * @return void
     */
    public function testWriteErrorLog(): void
    {
        $fs = new FakeFileSystem();
        $clock = new FakeClock(strtotime('2025-09-12 12:00:00'));
        $logger = new Logger($fs, $clock);

        $logger->error('Something failed');

        $expectedFile = 'php://stderr';
        $this->assertStringContainsString('[ERROR] Something failed', $fs->getFileContent($expectedFile) ?? '');
    }

    /**
     * Test that logging is disabled when debug=false.
     *
     * @return void
     */
    public function testLogDisabledWhenDebugIsFalse(): void
    {
        // Arrange: logger with debug disabled
        $fs = new FakeFileSystem();
        $clock = new FakeClock(strtotime('2025-09-12 12:00:00'));
        $logger = new Logger($fs, $clock, false); // debug = false

        // Act: try to log
        $logger->info('Should not be logged');

        // Assert: no log file should be created
        $expectedFile = 'php://stdout';
        $this->assertFalse($fs->hasFile($expectedFile), 'Nothing should be written when debug=false');
    }
}
