<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\StopFlow;
use Switon\Core\Tests\TestCase;

/**
 * Test cases for StopFlow class.
 *
 * Tests controlled flow interruption exception.
 */
class StopFlowTest extends TestCase
{
    /**
     * Test that StopFlow::because() creates exception with reason.
     */
    public function testStopFlowBecauseCreatesExceptionWithReason(): void
    {
        $exception = StopFlow::because('User authentication failed');

        $this->assertStringContainsString('User authentication failed', $exception->getMessage());
    }

    /**
     * Test that StopFlow::because() accepts context data.
     */
    public function testStopFlowBecauseAcceptsContext(): void
    {
        $exception = StopFlow::because('Rate limit exceeded', ['userId' => 123, 'limit' => 100]);

        $context = $exception->getContext();
        $this->assertArrayHasKey('userId', $context);
        $this->assertSame(123, $context['userId']);
    }

    /**
     * Test that StopFlow::abort() creates exception with default message.
     */
    public function testStopFlowAbortCreatesException(): void
    {
        $exception = StopFlow::abort();

        $this->assertStringContainsString('Process aborted', $exception->getMessage());
    }

    /**
     * Test that StopFlow::abort() accepts context data.
     */
    public function testStopFlowAbortAcceptsContext(): void
    {
        $exception = StopFlow::abort(['reason' => 'timeout']);

        $context = $exception->getContext();
        $this->assertArrayHasKey('reason', $context);
        $this->assertSame('timeout', $context['reason']);
    }

    /**
     * Test that StopFlow can be thrown and caught.
     */
    public function testStopFlowCanBeThrownAndCaught(): void
    {
        try {
            throw StopFlow::because('Test interruption');
            $this->fail('StopFlow should have been thrown');
        } catch (StopFlow $e) {
            $this->assertStringContainsString('Test interruption', $e->getMessage());
        }
    }
}
