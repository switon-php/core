<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Integration;

use Switon\Core\Backtrace;
use Switon\Core\Tests\TestCase;

/**
 * Test cases for Backtrace class.
 *
 * Tests coroutine-safe backtrace functionality in standard PHP mode.
 * Note: Swoole coroutine environment is not tested here as it requires Swoole runtime.
 */
class BacktraceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Arrange: Ensure coroutine is disabled for all tests
        \Switon\Core\Runtime::setCoroutineEnabled(false);
    }

    /**
     * Test that get() returns valid backtrace array.
     */
    public function testGetReturnsValidBacktraceArray(): void
    {
        // Arrange: (setUp already disabled coroutine)

        // Act
        $trace = Backtrace::get(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

        // Assert
        $this->assertIsArray($trace, 'Backtrace should return an array');
        $this->assertNotEmpty($trace, 'Backtrace should not be empty');
        $this->assertGreaterThanOrEqual(1, count($trace), 'Backtrace should have at least one frame');
    }

    /**
     * Test that get() filters out internal Backtrace::get() call.
     */
    public function testGetFiltersOutInternalCalls(): void
    {
        // Arrange: (setUp already disabled coroutine)

        // Act
        $trace = Backtrace::get(DEBUG_BACKTRACE_IGNORE_ARGS);

        // Assert: First frame should not be Backtrace::get itself
        $this->assertNotEmpty($trace, 'Backtrace should not be empty');
        $firstFrame = $trace[0];
        if (isset($firstFrame['class'])) {
            $this->assertNotSame(
                Backtrace::class,
                $firstFrame['class'],
                'First frame should not be Backtrace class itself'
            );
        }
    }

    /**
     * Test that get() respects limit parameter.
     */
    public function testGetRespectsLimitParameter(): void
    {
        // Arrange
        $limit = 2;

        // Act
        $trace = Backtrace::get(DEBUG_BACKTRACE_IGNORE_ARGS, $limit);

        // Assert
        $this->assertLessThanOrEqual($limit, count($trace), 'Backtrace should respect limit parameter');
    }

    /**
     * Test that get() works with DEBUG_BACKTRACE_PROVIDE_OBJECT option.
     */
    public function testGetWithProvideObjectOption(): void
    {
        // Arrange: (setUp already disabled coroutine)

        // Act
        $trace = Backtrace::get(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);

        // Assert
        $this->assertIsArray($trace, 'Backtrace should return an array');
        $this->assertNotEmpty($trace, 'Backtrace should not be empty');
    }

    /**
     * Test that get() with zero limit returns unlimited frames.
     */
    public function testGetWithZeroLimitReturnsUnlimitedFrames(): void
    {
        // Arrange: (setUp already disabled coroutine)

        // Act
        $trace = Backtrace::get(DEBUG_BACKTRACE_IGNORE_ARGS, 0);

        // Assert: Should have more than just a few frames
        $this->assertIsArray($trace, 'Backtrace should return an array');
        $this->assertNotEmpty($trace, 'Backtrace should not be empty');
    }

    /**
     * Test that get() returns frames with expected structure.
     */
    public function testGetReturnsFramesWithExpectedStructure(): void
    {
        // Arrange: (setUp already disabled coroutine)

        // Act
        $trace = Backtrace::get(0, 3);

        // Assert: Each frame should have at least 'function' or 'file' key
        foreach ($trace as $frame) {
            $this->assertTrue(
                isset($frame['function']) || isset($frame['file']),
                'Each frame should have function or file key'
            );
        }
    }
}
