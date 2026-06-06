<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\Tests\TestCase;

use const PHP_BINARY;

/**
 * Covers {@see \Switon\Core\Runtime::getRoot()} when Composer autoload is not among included files (subprocess).
 */
final class RuntimeRootDetectionTest extends TestCase
{
    public function testGetRootThrowsWhenAutoloadPhpNotLoadedInProcess(): void
    {
        $fixture = dirname(__DIR__) . '/Fixtures/runtime_root_detection_fail.php';
        $this->assertFileExists($fixture);

        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($fixture);
        exec($cmd . ' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
    }
}
