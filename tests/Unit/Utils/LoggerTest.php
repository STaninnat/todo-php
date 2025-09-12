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
class LoggerTest extends TestCase
{
    /**
     * Test that an info log is correctly written with timestamp and level.
     *
     * @return void
     */
    public function testWriteInfoLog(): void
    {
        // Arrange: fake file system + fixed clock at 2025-09-12 10:00:00
        $fs = new FakeFileSystem();
        $clock = new FakeClock(strtotime('2025-09-12 10:00:00'));
        $logger = new Logger('/logs', $fs, $clock);

        // Act: write an info log
        $logger->info('Hello World');

        // Assert: log file exists with expected content
        $expectedFile = '/logs/router-2025-09-12.log';
        $this->assertTrue($fs->hasFile($expectedFile));
        $this->assertStringContainsString(
            '[2025-09-12 10:00:00][INFO] Hello World',
            $fs->getFileContent($expectedFile)
        );
    }

    /**
     * Test that a warning log is written correctly.
     *
     * @return void
     */
    public function testWriteWarningLog(): void
    {
        $fs = new FakeFileSystem();
        $clock = new FakeClock(strtotime('2025-09-12 11:30:00'));
        $logger = new Logger('/logs', $fs, $clock);

        $logger->warning('Something odd');

        $expectedFile = '/logs/router-2025-09-12.log';
        $this->assertStringContainsString('[WARNING] Something odd', $fs->getFileContent($expectedFile));
    }

    /**
     * Test that an error log is written correctly.
     *
     * @return void
     */
    public function testWriteErrorLog(): void
    {
        $fs = new FakeFileSystem();
        $clock = new FakeClock(strtotime('2025-09-12 12:00:00'));
        $logger = new Logger('/logs', $fs, $clock);

        $logger->error('Something failed');

        $expectedFile = '/logs/router-2025-09-12.log';
        $this->assertStringContainsString('[ERROR] Something failed', $fs->getFileContent($expectedFile));
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
        $logger = new Logger('/logs', $fs, $clock, false); // debug = false

        // Act: try to log
        $logger->info('Should not be logged');

        // Assert: no log file should be created
        $expectedFile = '/logs/router-2025-09-12.log';
        $this->assertFalse($fs->hasFile($expectedFile), 'File should not be created when debug=false');
    }

    /**
     * Test that old logs are cleaned up after exceeding maxDays.
     *
     * @return void
     */
    public function testCleanupOldLogs(): void
    {
        $fs = new FakeFileSystem();
        $clock = new FakeClock(strtotime('2025-09-12 12:00:00'));

        // Arrange: create a log file older than 30 days
        $oldFile = '/logs/router-2025-08-01.log';
        $fs->write($oldFile, 'old log', false);

        // Act: instantiate logger (constructor triggers cleanup) and write a new log
        $logger = new Logger('/logs', $fs, $clock, true, 30);
        $logger->info('new log');

        $expectedFile = '/logs/router-2025-09-12.log';

        // Assert: old log deleted, new log created
        $this->assertFalse($fs->hasFile($oldFile), 'Old log should be deleted');
        $this->assertTrue($fs->hasFile($expectedFile), 'New log should exist');
    }

    /**
     * Test that setMaxDays() changes cleanup behavior.
     *
     * @return void
     */
    public function testSetMaxDaysChangesCleanupBehavior(): void
    {
        $fs = new FakeFileSystem();
        $clock = new FakeClock(strtotime('2025-09-12 12:00:00'));

        // Arrange: file 10 days old
        $oldFile = '/logs/router-2025-09-02.log';
        $fs->write($oldFile, 'old log', false);

        // Act: logger initially keeps 30 days, then reduced to 5 days
        $logger = new Logger('/logs', $fs, $clock, true, 30);
        $logger->setMaxDays(5);
        $logger->info('trigger cleanup');

        // Assert: file older than 5 days should be deleted
        $this->assertFalse($fs->hasFile($oldFile), 'File older than 5 days should be deleted');
    }
}
