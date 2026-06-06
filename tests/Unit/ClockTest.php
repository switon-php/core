<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\Attribute\Autowired;
use Switon\Core\Clock;
use Switon\Core\ClockInterface;
use Switon\Core\Tests\TestCase;

/**
 * Test cases for Clock class.
 *
 * Tests system clock implementation for time and microtime operations.
 */
class ClockTest extends TestCase
{
    #[Autowired] protected ClockInterface $clock;

    /**
     * Test that Clock implements ClockInterface.
     */
    public function testClockImplementsClockInterface(): void
    {
        $this->assertInstanceOf(ClockInterface::class, $this->clock);
    }

    /**
     * Test that time() returns an integer timestamp.
     */
    public function testTimeReturnsInteger(): void
    {
        $time = $this->clock->time();

        $this->assertIsInt($time);
    }

    /**
     * Test that time() returns a reasonable timestamp.
     */
    public function testTimeReturnsReasonableTimestamp(): void
    {
        $time = $this->clock->time();

        // Should be greater than 2020-01-01 (1577836800)
        $this->assertGreaterThan(1577836800, $time);

        // Should be less than 2100-01-01 (4102444800)
        $this->assertLessThan(4102444800, $time);
    }

    /**
     * Test that time() returns current Unix timestamp.
     */
    public function testTimeReturnsCurrentTimestamp(): void
    {
        $before = time();
        $clockTime = $this->clock->time();
        $after = time();

        // Clock time should be between before and after
        $this->assertGreaterThanOrEqual($before, $clockTime);
        $this->assertLessThanOrEqual($after, $clockTime);
    }

    /**
     * Test that microtime() returns a float.
     */
    public function testMicrotimeReturnsFloat(): void
    {
        $microtime = $this->clock->microtime();

        $this->assertIsFloat($microtime);
    }

    /**
     * Test that microtime() returns a reasonable timestamp.
     */
    public function testMicrotimeReturnsReasonableTimestamp(): void
    {
        $microtime = $this->clock->microtime();

        // Should be greater than 2020-01-01 (1577836800.0)
        $this->assertGreaterThan(1577836800.0, $microtime);

        // Should be less than 2100-01-01 (4102444800.0)
        $this->assertLessThan(4102444800.0, $microtime);
    }

    /**
     * Test that microtime() has decimal precision.
     */
    public function testMicrotimeHasDecimalPart(): void
    {
        $microtime = $this->clock->microtime();

        // Microtime should have decimal part (not just integer seconds)
        // We check this by verifying it's not equal to its integer part
        $this->assertNotSame((float)(int)$microtime, $microtime);
    }

    /**
     * Test that microtime() returns current Unix timestamp with microseconds.
     */
    public function testMicrotimeReturnsCurrentTimestamp(): void
    {
        $before = microtime(true);
        $clockMicrotime = $this->clock->microtime();
        $after = microtime(true);

        // Clock microtime should be between before and after
        $this->assertGreaterThanOrEqual($before, $clockMicrotime);
        $this->assertLessThanOrEqual($after, $clockMicrotime);
    }

    /**
     * Test that microtime() is more precise than time().
     */
    public function testMicrotimeIsMorePreciseThanTime(): void
    {
        $time = $this->clock->time();
        $microtime = $this->clock->microtime();

        // Microtime integer part should match time (within 1 second tolerance)
        $this->assertEqualsWithDelta($time, (int)$microtime, 1);

        // Microtime should have fractional part
        $fractionalPart = $microtime - floor($microtime);
        $this->assertGreaterThanOrEqual(0.0, $fractionalPart);
        $this->assertLessThan(1.0, $fractionalPart);
    }

    /**
     * Test that consecutive time() calls return non-decreasing values.
     */
    public function testConsecutiveTimeCallsReturnNonDecreasingValues(): void
    {
        $time1 = $this->clock->time();
        $time2 = $this->clock->time();

        // Time should not decrease (may be equal if called within same second)
        $this->assertGreaterThanOrEqual($time1, $time2);
    }

    /**
     * Test that Clock can be instantiated multiple times.
     */
    public function testClockCanBeInstantiatedMultipleTimes(): void
    {
        $clock1 = new Clock();
        $clock2 = new Clock();

        $time1 = $clock1->time();
        $time2 = $clock2->time();

        // Both should return similar timestamps
        $this->assertEqualsWithDelta($time1, $time2, 1);
    }
}
