<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use JsonSerializable;
use Switon\Core\Strings;
use Switon\Core\Tests\TestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * Test cases for Strings class.
 *
 * Tests general string manipulation utilities.
 * For handler IDs and FQCN id(), see ClassNameTest.
 */
class StringsTest extends TestCase
{
    /**
     * Test that chain() builds chain string from elements.
     */
    public function testChainBuildsChainString(): void
    {
        // Arrange
        $elements = ['A', 'B', 'C'];

        // Act
        $result = Strings::chain($elements);

        // Assert
        $this->assertSame('A -> B -> C', $result);
    }

    /**
     * Test that chain() uses custom separator.
     */
    public function testChainUsesCustomSeparator(): void
    {
        // Arrange
        $elements = ['X', 'Y', 'Z'];

        // Act
        $result = Strings::chain($elements, ' | ');

        // Assert
        $this->assertSame('X | Y | Z', $result);
    }

    /**
     * Test that chain() appends element when provided.
     */
    public function testChainAppendsElement(): void
    {
        // Arrange
        $elements = ['service1', 'service2'];

        // Act
        $result = Strings::chain($elements, ' -> ', 'service3');

        // Assert
        $this->assertSame('service1 -> service2 -> service3', $result);
    }

    /**
     * Test that chain() handles empty array.
     */
    public function testChainHandlesEmptyArray(): void
    {
        // Arrange & Act
        $result = Strings::chain([]);

        // Assert
        $this->assertSame('', $result);
    }

    /**
     * Test that chain() handles single element.
     */
    public function testChainHandlesSingleElement(): void
    {
        // Arrange & Act
        $result = Strings::chain(['only']);

        // Assert
        $this->assertSame('only', $result);
    }

    /**
     * Test that chain() handles empty array with append.
     */
    public function testChainHandlesEmptyArrayWithAppend(): void
    {
        // Arrange & Act
        $result = Strings::chain([], ' -> ', 'appended');

        // Assert
        $this->assertSame('appended', $result);
    }

    // ========== interpolate() tests ==========

    public function testInterpolateWithStringValues(): void
    {
        $message = 'Hello {name}, you are {age} years old';
        $context = ['name' => 'John', 'age' => '30'];

        $result = Strings::interpolate($message, $context);

        $this->assertSame('Hello John, you are 30 years old', $result);
    }

    public function testInterpolateWithIntegerValues(): void
    {
        $message = 'Count: {count}';
        $context = ['count' => 42];

        $result = Strings::interpolate($message, $context);

        $this->assertSame('Count: 42', $result);
    }

    public function testInterpolateWithFloatValues(): void
    {
        $message = 'Price: {price}';
        $context = ['price' => 19.99];

        $result = Strings::interpolate($message, $context);

        $this->assertSame('Price: 19.99', $result);
    }

    public function testInterpolateWithBooleanValues(): void
    {
        $message = 'Active: {active}';
        $context = ['active' => true];

        $result = Strings::interpolate($message, $context);

        $this->assertSame('Active: true', $result);
    }

    public function testInterpolateWithArrayValues(): void
    {
        $message = 'Items: {items}';
        $context = ['items' => ['a', 'b', 'c']];

        $result = Strings::interpolate($message, $context);

        $this->assertStringContainsString('Items:', $result);
        $this->assertStringContainsString('"a"', $result);
    }

    public function testInterpolateWithObjectValues(): void
    {
        $message = 'Data: {data}';
        $context = ['data' => (object)['key' => 'value']];

        $result = Strings::interpolate($message, $context);

        $this->assertStringContainsString('Data:', $result);
        $this->assertStringContainsString('"key"', $result);
    }

    public function testInterpolateWithJsonSerializable(): void
    {
        $jsonSerializable = new class () implements JsonSerializable {
            public function jsonSerialize(): mixed
            {
                return ['key' => 'value'];
            }
        };

        $message = 'Data: {data}';
        $context = ['data' => $jsonSerializable];

        $result = Strings::interpolate($message, $context);

        $this->assertStringContainsString('Data:', $result);
        $this->assertStringContainsString('"key"', $result);
    }

    public function testInterpolateWithStringable(): void
    {
        $stringable = new class () {
            public function __toString(): string
            {
                return 'stringable-value';
            }
        };

        $message = 'Value: {value}';
        $context = ['value' => $stringable];

        $result = Strings::interpolate($message, $context);

        $this->assertSame('Value: stringable-value', $result);
    }

    public function testInterpolateWithMissingContextKeys(): void
    {
        $message = 'Hello {name}, missing: {missing}';
        $context = ['name' => 'John'];

        $result = Strings::interpolate($message, $context);

        $this->assertSame('Hello John, missing: {missing}', $result);
    }

    public function testInterpolateWithNullValues(): void
    {
        $message = 'Value: {value}';
        $context = ['value' => null];

        $result = Strings::interpolate($message, $context);

        $this->assertSame('Value: {value}', $result);
    }

    /**
     * Values that are not string/scalar/array/object (e.g. resource) cannot be rendered; placeholder stays literal.
     * Documents {@see Strings::interpolate()} contract for log templates with partial context.
     */
    public function testInterpolateLeavesPlaceholderWhenValueCannotBeRendered(): void
    {
        $h = fopen('php://memory', 'rb');
        $this->assertNotFalse($h);

        try {
            $result = Strings::interpolate('pid {x}', ['x' => $h]);
        } finally {
            fclose($h);
        }

        $this->assertSame('pid {x}', $result);
    }

    public function testInterpolateWithNestedKeys(): void
    {
        $message = 'User: {user.name}';
        $context = ['user.name' => 'John'];

        $result = Strings::interpolate($message, $context);

        $this->assertSame('User: John', $result);
    }

    public function testInterpolateWithStringableMessage(): void
    {
        $message = new class () {
            public function __toString(): string
            {
                return 'Hello {name}';
            }
        };
        $context = ['name' => 'John'];

        $result = Strings::interpolate($message, $context);

        $this->assertSame('Hello John', $result);
    }

    // ========== renderException() tests ==========

    public function testRenderExceptionFormatsException(): void
    {
        $exception = new RuntimeException('Test exception');

        $result = Strings::renderException($exception);

        $this->assertStringContainsString('RuntimeException: Test exception', $result);
        $this->assertStringContainsString('at', $result);
    }

    /**
     * Asserts the documented output format: "ClassName: message", then "    at file:line", then "    at file(line): ...".
     * Keeps docs/en/log/README.md exception example in sync with Strings::renderException().
     */
    public function testRenderExceptionOutputFormat(): void
    {
        $exception = new RuntimeException('Insufficient funds');

        $result = Strings::renderException($exception);

        $lines = explode("\n", $result);
        $this->assertGreaterThanOrEqual(2, count($lines), 'At least exception line and one at line');

        // First line: Full\ClassName: message (no "Stack trace:" label)
        $this->assertStringContainsString('RuntimeException: Insufficient funds', $lines[0]);
        $this->assertStringNotContainsString('Stack trace', $result);

        // Second line: "    at file:line"
        $this->assertMatchesRegularExpression('/^\s+at .+:\d+$/', $lines[1], 'Second line must be "    at file:line"');

        // Trace lines use "    at " prefix (not "#0 ")
        foreach (array_slice($lines, 2) as $line) {
            if ($line === '') {
                continue;
            }
            $this->assertStringStartsWith('    at ', $line, 'Trace lines must start with "    at "');
        }
    }

    public function testRenderExceptionWithPreviousException(): void
    {
        $previous = new InvalidArgumentException('Previous exception');
        $exception = new RuntimeException('Test exception', 0, $previous);

        $result = Strings::renderException($exception);

        $this->assertStringContainsString('RuntimeException: Test exception', $result);
        $this->assertStringContainsString('Caused by', $result);
        $this->assertStringContainsString('InvalidArgumentException: Previous exception', $result);
    }

    /** Previous created in another stack frame so trace strings differ from the wrapping exception. */
    private static function newInvalidArgumentForRenderChain(string $message): InvalidArgumentException
    {
        return new InvalidArgumentException($message);
    }

    public function testRenderExceptionAppendsPreviousTraceWhenStacksDiffer(): void
    {
        $previous = self::newInvalidArgumentForRenderChain('inner');
        $exception = new RuntimeException('outer', 0, $previous);

        $result = Strings::renderException($exception);

        $this->assertStringContainsString('Caused by', $result);
        $this->assertStringNotContainsString('    at ...', $result);
        $this->assertStringContainsString('newInvalidArgumentForRenderChain', $result);
    }

    public function testRenderExceptionWithRootStripsPrefix(): void
    {
        $exception = new RuntimeException('Test exception');

        $result = Strings::renderException($exception, __DIR__);

        $this->assertStringContainsString('RuntimeException: Test exception', $result);
        // Path prefix should be stripped
        $this->assertStringNotContainsString(__DIR__, $result);
    }
}
