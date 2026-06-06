<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\ArrayableInterface;
use Switon\Core\Exception\JsonException;
use Switon\Core\Json;
use Switon\Core\Tests\TestCase;
use DateTimeImmutable;
use JsonSerializable;
use Stringable;

use function get_object_vars;

/**
 * Test cases for Json class.
 *
 * Tests JSON encoding and decoding operations with comprehensive error handling.
 */
class JsonTest extends TestCase
{
    /**
     * Test that parse() correctly parses valid JSON strings.
     */
    public function testParseWithValidJson(): void
    {
        $json = '{"name":"John","age":30,"active":true}';
        $result = Json::parse($json);

        $this->assertIsArray($result);
        $this->assertSame('John', $result['name']);
        $this->assertSame(30, $result['age']);
        $this->assertTrue($result['active']);
    }

    /**
     * Test that parse() throws exception for invalid JSON.
     */
    public function testParseThrowsExceptionForInvalidJson(): void
    {
        $this->expectException(JsonException::class);
        Json::parse('{"name": "John", "age": }');
    }

    /**
     * Test that stringify() correctly encodes PHP arrays to JSON.
     */
    public function testStringifyWithArray(): void
    {
        $data = ['name' => 'John', 'age' => 30, 'active' => true];
        $result = Json::stringify($data);

        $this->assertIsString($result);
        $parsed = json_decode($result, true);
        $this->assertSame('John', $parsed['name']);
        $this->assertSame(30, $parsed['age']);
        $this->assertTrue($parsed['active']);
    }

    public function testJsonHelperDelegatesToStringify(): void
    {
        $data = ['name' => 'John', 'quote' => '"'];

        $this->assertSame(Json::stringify($data), json($data));
    }

    /**
     * Test that parse() and stringify() are inverse operations.
     */
    public function testParseAndStringifyAreInverse(): void
    {
        $original = ['name' => 'John', 'age' => 30, 'nested' => ['key' => 'value']];
        $json = Json::stringify($original);
        $parsed = Json::parse($json);

        $this->assertSame($original, $parsed);
    }

    /**
     * Test that parse() handles null values correctly.
     */
    public function testParseWithNullValue(): void
    {
        $result = Json::parse('null');
        $this->assertNull($result);
    }

    /**
     * Test that stringify() handles Unicode characters correctly.
     */
    public function testStringifyWithUnicodeCharacters(): void
    {
        $data = ['message' => 'Hello 世界', 'emoji' => '🚀'];
        $result = Json::stringify($data);

        $this->assertStringContainsString('世界', $result);
        $this->assertStringContainsString('🚀', $result);
    }

    /**
     * Test that stringify() with JSON_PRETTY_PRINT option formats output.
     */
    public function testStringifyWithPrettyPrint(): void
    {
        $data = ['name' => 'John', 'age' => 30];
        $result = Json::stringify($data, JSON_PRETTY_PRINT);

        $this->assertStringContainsString("\n", $result);
        $parsed = Json::parse($result);
        $this->assertSame('John', $parsed['name']);
        $this->assertSame(30, $parsed['age']);
    }

    /**
     * Test that stringify() throws exception for invalid data.
     */
    public function testStringifyThrowsExceptionForInvalidData(): void
    {
        // Create data that cannot be JSON encoded (circular reference)
        $data = [];
        $data['self'] = &$data;

        $this->expectException(JsonException::class);
        Json::stringify($data);
    }

    /**
     * Test that parse() handles the null string correctly.
     *
     * Verifies that parse() correctly handles the string 'null' vs actual null value.
     */
    public function testParseHandlesNullStringCorrectly(): void
    {
        // 'null' string should return null
        $result = Json::parse('null');
        $this->assertNull($result);

        // Empty object should return array
        $result = Json::parse('{}');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that stringify() applies default options correctly.
     */
    public function testStringifyAppliesDefaultOptions(): void
    {
        $data = ['path' => '/test/path', 'unicode' => '测试'];
        $result = Json::stringify($data);

        // Should not escape slashes
        $this->assertStringContainsString('/test/path', $result);
        // Should not escape unicode
        $this->assertStringContainsString('测试', $result);
    }

    /**
     * Test that stringify() with custom options combines with defaults.
     */
    public function testStringifyCombinesCustomOptionsWithDefaults(): void
    {
        $data = ['name' => 'John', 'age' => 30];
        $result = Json::stringify($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Should have pretty print (newlines)
        $this->assertStringContainsString("\n", $result);
        // Should still parse correctly
        $parsed = Json::parse($result);
        $this->assertSame('John', $parsed['name']);
    }

    public function testNormalizeObjectVarsConvertsJsonSafeValues(): void
    {
        $payload = new class () {
            public mixed $created_at = null;
            public mixed $details = null;
            public mixed $items = [];
            public mixed $ignored = null;
        };

        $payload->created_at = new DateTimeImmutable('2024-01-02 03:04:05+00:00');
        $payload->details = new class () implements ArrayableInterface {
            public function toArray(): array
            {
                return [
                    'id' => 11,
                    'updated_at' => new DateTimeImmutable('2024-01-03 04:05:06+00:00'),
                ];
            }
        };
        $payload->items = [
            new class () implements JsonSerializable {
                public function jsonSerialize(): array
                {
                    return ['key' => 'val'];
                }
            },
            new class () {
                public function toArray(): array
                {
                    return ['id' => 99];
                }
            },
        ];
        $payload->ignored = new class () {
            public function toArray(): array
            {
                return ['id' => 100];
            }
        };

        $result = Json::normalizeArray(get_object_vars($payload));

        $this->assertSame('2024-01-02T03:04:05+00:00', $result['created_at']);
        $this->assertSame(
            [
                'id' => 11,
                'updated_at' => '2024-01-03T04:05:06+00:00',
            ],
            $result['details']
        );
        $this->assertSame(
            [
                ['key' => 'val'],
                null,
            ],
            $result['items']
        );
        $this->assertArrayNotHasKey('ignored', $result);
    }

    /**
     * Test that parse() handles empty string.
     */
    public function testParseHandlesEmptyString(): void
    {
        $this->expectException(JsonException::class);
        Json::parse('');
    }

    /**
     * Test that parse() handles depth limit exceeded.
     */
    public function testParseHandlesDepthLimit(): void
    {
        // Create deeply nested JSON (more than 64 levels)
        $deep = [];
        $current = &$deep;
        for ($i = 0; $i < 65; $i++) {
            $current['nested'] = [];
            $current = &$current['nested'];
        }
        // Increase depth limit for json_encode to ensure it can generate the test string
        $json = json_encode($deep, 0, 512);

        if ($json === false) {
            $this->fail('Failed to create test JSON: ' . json_last_error_msg());
        }

        $this->expectException(JsonException::class);
        Json::parse($json);
    }

    public function testNormalizeArraySupportsBackedAndUnitEnums(): void
    {
        $payload = new class () {
            public mixed $status = null;
            public mixed $phase = null;
        };

        $payload->status = JsonTestBackedEnum::Active;
        $payload->phase = JsonTestUnitEnum::Alpha;

        $result = Json::normalizeArray(get_object_vars($payload));

        $this->assertSame('active', $result['status']);
        $this->assertSame('Alpha', $result['phase']);
    }

    public function testNormalizeArraySupportsStringable(): void
    {
        $payload = new class () {
            public mixed $label = null;
        };

        $payload->label = new class () implements Stringable {
            public function __toString(): string
            {
                return 'ok';
            }
        };

        $result = Json::normalizeArray(get_object_vars($payload));

        $this->assertSame('ok', $result['label']);
    }

    public function testNormalizeArrayOmitsPropertiesWhoseValueIsNull(): void
    {
        $payload = new class () {
            public ?string $onlyNull = null;
            public string $kept = 'x';
        };

        $result = Json::normalizeArray(get_object_vars($payload));

        $this->assertArrayNotHasKey('onlyNull', $result);
        $this->assertSame('x', $result['kept']);
    }

    public function testNormalizeArrayPreservesResourceInsideArrayableToArray(): void
    {
        $handle = fopen('php://memory', 'r');
        $this->assertNotFalse($handle);

        try {
            $payload = new class ($handle) implements ArrayableInterface {
                public function __construct(private mixed $h)
                {
                }

                public function toArray(): array
                {
                    return ['stream' => $this->h];
                }
            };

            $wrapper = new class () {
                public mixed $nested = null;
            };
            $wrapper->nested = $payload;

            $result = Json::normalizeArray(get_object_vars($wrapper));

            $this->assertIsArray($result['nested']);
            $this->assertArrayHasKey('stream', $result['nested']);
            $this->assertIsResource($result['nested']['stream']);
        } finally {
            fclose($handle);
        }
    }
}

enum JsonTestBackedEnum: string
{
    case Active = 'active';
}

enum JsonTestUnitEnum
{
    case Alpha;
}
