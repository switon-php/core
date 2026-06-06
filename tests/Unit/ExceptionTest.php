<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use RuntimeException;
use Switon\Core\Exception;
use Switon\Core\Tests\Fixtures\TestException;
use Switon\Core\Tests\TestCase;

/**
 * Test cases for Exception base class.
 *
 * Tests exception creation, message templates, context handling, and JSON formatting.
 */
class ExceptionTest extends TestCase
{
    /**
     * Test that Exception::of() creates exception instance with correct defaults.
     *
     * This is a basic regression test ensuring the factory method works with minimal parameters.
     * More complex scenarios are tested in other methods.
     */
    public function testExceptionOfCreatesInstance(): void
    {
        $exception = Exception::of('Test error message');

        $this->assertSame('Test error message', $exception->getMessage());
        $this->assertSame(0, $exception->getCode()); // Default code
        $this->assertEmpty($exception->getContext()); // No context provided
    }

    /**
     * Test that Exception::of() supports message templates with placeholders.
     */
    public function testExceptionOfSupportsMessageTemplates(): void
    {
        $exception = Exception::of('User {id} not found', ['id' => 123]);

        $this->assertSame('User 123 not found', $exception->getMessage());
    }

    /**
     * Test that Exception::of() stores context data.
     */
    public function testExceptionOfStoresContext(): void
    {
        $exception = Exception::of('Error occurred', [
            'id' => 123,
            'user_agent' => 'Mozilla/5.0 (compatible; TestBot/1.0)',
        ]);

        $context = $exception->getContext();
        $this->assertArrayHasKey('user_agent', $context);
        $this->assertSame('Mozilla/5.0 (compatible; TestBot/1.0)', $context['user_agent']);
    }

    /**
     * Test that Exception::of() removes placeholder keys from context.
     */
    public function testExceptionOfRemovesPlaceholderKeysFromContext(): void
    {
        $exception = Exception::of('User {id} not found', [
            'id' => 123,
            'extra_info' => 'additional context data',
        ]);

        $context = $exception->getContext();
        // 'id' should be removed from context (used in placeholder)
        $this->assertArrayNotHasKey('id', $context);
        // 'extra_info' should remain in context (not used in placeholder)
        $this->assertArrayHasKey('extra_info', $context);
    }

    /**
     * Test that Exception::of() handles non-string context values in placeholders.
     */
    public function testExceptionOfHandlesNonStringContextValues(): void
    {
        $exception = Exception::of('User {id} has {count} orders', [
            'id' => 123,
            'count' => 5,
        ]);

        $this->assertStringContainsString('123', $exception->getMessage());
        $this->assertStringContainsString('5', $exception->getMessage());
    }

    /**
     * Test that Exception::of() can wrap another Throwable.
     */
    public function testExceptionOfCanWrapThrowable(): void
    {
        $previous = new RuntimeException('Original error');
        $exception = Exception::of($previous, ['context' => 'data']);

        $this->assertSame('Original error', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
        $context = $exception->getContext();
        $this->assertArrayHasKey('context', $context);
    }

    /**
     * Test that Exception::raise() throws exception.
     */
    public function testExceptionRaiseThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test error');

        Exception::raise('Test error');
    }

    /**
     * Test that Exception::raise() supports message templates.
     */
    public function testExceptionRaiseSupportsMessageTemplates(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User 123 not found');

        Exception::raise('User {id} not found', ['id' => 123]);
    }

    /**
     * Test that getStatusCode() returns default 500.
     */
    public function testGetStatusCodeReturnsDefault(): void
    {
        $exception = Exception::of('Test error');

        $this->assertSame(500, $exception->getStatusCode());
    }

    /**
     * Test that getJson() returns correct format.
     */
    public function testGetJsonReturnsCorrectFormat(): void
    {
        $exception = Exception::of('Test error');

        $json = $exception->getJson();

        $this->assertIsArray($json);
        $this->assertArrayHasKey('code', $json);
        $this->assertArrayHasKey('msg', $json);
        $this->assertSame(500, $json['code']);
        $this->assertSame('Test error', $json['msg']);
    }

    /**
     * Test that getJson() uses custom message for non-500 status codes.
     */
    public function testGetJsonUsesCustomMessageForNon500Status(): void
    {
        $exception = TestException::of('Custom error');
        $exception->setStatusCode(404);

        $json = $exception->getJson();

        $this->assertSame(404, $json['code']);
        $this->assertSame('Custom error', $json['msg']);
    }

    /**
     * Test that getJson() converts status code 200 to 0.
     */
    public function testGetJsonConverts200To0(): void
    {
        $exception = TestException::of('Success');
        $exception->setStatusCode(200);

        $json = $exception->getJson();

        $this->assertSame(0, $json['code']);
        $this->assertSame('Success', $json['msg']);
    }

    /**
     * Test that getContext() returns stored context.
     */
    public function testGetContextReturnsStoredContext(): void
    {
        $exception = Exception::of('Error', [
            'key1' => 'value1',
            'key2' => 'value2',
        ]);

        $context = $exception->getContext();

        $this->assertIsArray($context);
        $this->assertSame('value1', $context['key1']);
        $this->assertSame('value2', $context['key2']);
    }

    /**
     * Test that getContext() returns empty array when no context.
     */
    public function testGetContextReturnsEmptyArrayWhenNoContext(): void
    {
        $exception = Exception::of('Error');

        $context = $exception->getContext();

        $this->assertIsArray($context);
        $this->assertEmpty($context);
    }

    /**
     * Test that getJson() can be overridden by setting json property.
     */
    public function testGetJsonCanBeOverridden(): void
    {
        $exception = TestException::of('Error');

        // Use test-specific method to set json property (better than reflection)
        $exception->setJsonForTesting(['code' => 999, 'msg' => 'Custom message']);

        $json = $exception->getJson();

        $this->assertSame(999, $json['code']);
        $this->assertSame('Custom message', $json['msg']);
    }
}
