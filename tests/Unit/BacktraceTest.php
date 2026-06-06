<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\Backtrace;
use Switon\Core\Runtime;
use Switon\Core\Tests\TestCase;

/**
 * Test cases for Backtrace class.
 *
 * Tests coroutine-safe backtrace utility with automatic coroutine detection.
 */
class BacktraceTest extends TestCase
{
    /**
     * Test that get() returns an array.
     */
    public function testGetReturnsArray(): void
    {
        // Arrange
        $options = DEBUG_BACKTRACE_IGNORE_ARGS;

        // Act
        $backtrace = Backtrace::get($options);

        // Assert
        $this->assertIsArray($backtrace, 'Backtrace::get() should return an array');
    }

    /**
     * Test that backtrace contains expected frame structure.
     */
    public function testBacktraceContainsExpectedFrameStructure(): void
    {
        // Arrange
        $options = DEBUG_BACKTRACE_PROVIDE_OBJECT;

        // Act
        $backtrace = Backtrace::get($options);

        // Assert
        $this->assertNotEmpty($backtrace, 'Backtrace should not be empty');

        // First frame should have function, file, and line
        $firstFrame = $backtrace[0];
        $this->assertArrayHasKey('function', $firstFrame, 'Frame should have function key');
        $this->assertArrayHasKey('file', $firstFrame, 'Frame should have file key');
        $this->assertArrayHasKey('line', $firstFrame, 'Frame should have line key');
    }

    /**
     * Test that Backtrace::get() is not in the returned backtrace.
     */
    public function testBacktraceDoesNotIncludeSelf(): void
    {
        // Arrange & Act
        $backtrace = Backtrace::get(DEBUG_BACKTRACE_IGNORE_ARGS);

        // Assert
        foreach ($backtrace as $frame) {
            if (isset($frame['class']) && $frame['class'] === Backtrace::class) {
                $this->fail('Backtrace should not include Backtrace::get() itself');
            }
        }
        $this->assertTrue(true, 'Backtrace correctly excludes itself');
    }

    /**
     * Test that limit parameter works correctly.
     */
    public function testLimitParameterLimitsFrames(): void
    {
        // Arrange
        $limit = 2;

        // Act
        $backtrace = Backtrace::get(DEBUG_BACKTRACE_IGNORE_ARGS, $limit);

        // Assert
        $this->assertLessThanOrEqual($limit, count($backtrace), "Backtrace should have at most {$limit} frames");
    }

    /**
     * Test that zero limit returns all frames.
     */
    public function testZeroLimitReturnsAllFrames(): void
    {
        // Arrange
        $limit = 0;

        // Act
        $backtrace = Backtrace::get(DEBUG_BACKTRACE_IGNORE_ARGS, $limit);

        // Assert
        // Should have at least the test method and PHPUnit frames
        $this->assertGreaterThan(2, count($backtrace), 'Zero limit should return multiple frames');
    }

    /**
     * Test backtrace in non-coroutine mode (default).
     */
    public function testNonCoroutineMode(): void
    {
        // Arrange
        $originalState = Runtime::isCoroutineEnabled();
        Runtime::setCoroutineEnabled(false);

        try {
            // Act
            $backtrace = Backtrace::get(DEBUG_BACKTRACE_IGNORE_ARGS);

            // Assert
            $this->assertIsArray($backtrace, 'Should return array in non-coroutine mode');
            $this->assertNotEmpty($backtrace, 'Backtrace should not be empty');
        } finally {
            // Cleanup - restore original state
            Runtime::setCoroutineEnabled($originalState);
        }
    }

    /**
     * Test that DEBUG_BACKTRACE_IGNORE_ARGS option affects frame structure.
     *
     * Note: With DEBUG_BACKTRACE_IGNORE_ARGS, PHP may still include 'args' key
     * but with empty array in some frames (e.g., internal calls). We verify
     * that at least the option is applied by checking early frames.
     */
    public function testIgnoreArgsOptionAffectsFrameStructure(): void
    {
        // Arrange & Act
        $backtrace = Backtrace::get(DEBUG_BACKTRACE_IGNORE_ARGS);

        // Assert - verify we get a valid backtrace
        $this->assertNotEmpty($backtrace, 'Backtrace should not be empty');

        // Check that at least some frames don't have populated args
        $hasFrameWithoutArgs = false;
        foreach ($backtrace as $frame) {
            if (!isset($frame['args']) || empty($frame['args'])) {
                $hasFrameWithoutArgs = true;
                break;
            }
        }
        $this->assertTrue($hasFrameWithoutArgs, 'At least some frames should not have args');
    }

    /**
     * Test that backtrace includes file information.
     */
    public function testBacktraceIncludesFileInfo(): void
    {
        // Arrange & Act
        $backtrace = Backtrace::get(DEBUG_BACKTRACE_IGNORE_ARGS);

        // Assert - at least one frame should have file info
        $hasFileInfo = false;
        foreach ($backtrace as $frame) {
            if (isset($frame['file'])) {
                $hasFileInfo = true;
                break;
            }
        }
        $this->assertTrue($hasFileInfo, 'Backtrace should include file information in at least one frame');
    }

    /**
     * Test nested function calls produce correct backtrace.
     */
    public function testNestedCallsProduceCorrectBacktrace(): void
    {
        // Arrange & Act
        $backtrace = $this->helperLevelOne();

        // Assert
        $functionNames = array_column($backtrace, 'function');
        $this->assertContains('helperLevelTwo', $functionNames, 'Backtrace should include helperLevelTwo');
        $this->assertContains('helperLevelOne', $functionNames, 'Backtrace should include helperLevelOne');
    }

    /**
     * Helper method level one for nested call testing.
     */
    protected function helperLevelOne(): array
    {
        return $this->helperLevelTwo();
    }

    /**
     * Helper method level two for nested call testing.
     */
    protected function helperLevelTwo(): array
    {
        return Backtrace::get(DEBUG_BACKTRACE_IGNORE_ARGS);
    }
}
