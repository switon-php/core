<?php

declare(strict_types=1);

namespace Switon\Core;

use BackedEnum;
use DateTimeInterface;
use JsonSerializable;
use Stringable;
use Switon\Core\Exception\JsonException;
use UnitEnum;

use function is_array;
use function is_object;
use function is_scalar;

/**
 * Safe JSON helper with exception-based error handling and JSON-safe normalization.
 *
 * @see \Switon\Core\ArrayableInterface
 * @see \Switon\Core\Exception\JsonException
 */
class Json
{
    /**
     * Parse JSON string to PHP value.
     *
     * @param string $str JSON string to parse
     *
     * @return mixed Parsed PHP value
     *
     * Decodes to associative arrays and throws `JsonException` on failure.
     *
     * @throws JsonException If parsing fails
     */
    public static function parse(string $str): mixed
    {
        try {
            return json_decode($str, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            JsonException::raise('JSON parse failed: "{str}".', ['str' => substr($str, 0, 100)]);
        }
    }

    /**
     * Normalize an array into JSON-safe array data.
     *
     * Top-level unknown objects are skipped; nested unknown objects become null to preserve array shape.
     * Null values are excluded from the top-level result.
     *
     * @param array<string, mixed> $objectVars Result from get_object_vars()
     *
     * @return array<string, mixed>
     */
    public static function normalizeArray(array $objectVars): array
    {
        $data = [];

        foreach ($objectVars as $field => $value) {
            if ($value === null) {
                continue;
            }

            $skip = false;
            $value = static::normalizeValue($value, $skip, false);
            if ($skip) {
                continue;
            }

            $data[$field] = $value;
        }

        return $data;
    }

    /**
     * Convert PHP value to JSON string.
     *
     * @param mixed $json PHP value to encode
     * @param int $options Additional JSON encoding options
     *
     * @return string JSON string
     *
     * Uses `JSON_THROW_ON_ERROR` with framework defaults and throws `JsonException` on failure.
     *
     * @throws JsonException If encoding fails
     */
    public static function stringify(mixed $json, int $options = 0): string
    {
        $options |= JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;

        try {
            return json_encode($json, $options, 64);
        } catch (\JsonException $e) {
            JsonException::raise('JSON encode failed: "{error}".', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Normalize one value for JSON-safe array output.
     *
     * @param mixed $value
     * @param bool $skip
     * @param bool $nested
     *
     * @return mixed
     */
    protected static function normalizeValue(mixed $value, bool &$skip, bool $nested): mixed
    {
        $skip = false;

        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof ArrayableInterface) {
            return static::normalizeNestedArray($value->toArray());
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof JsonSerializable) {
            $jsonValue = $value->jsonSerialize();
            return static::normalizeValue($jsonValue, $skip, true);
        }

        if ($value instanceof Stringable) {
            return (string)$value;
        }

        if (is_array($value)) {
            return static::normalizeNestedArray($value);
        }

        if (is_object($value)) {
            if ($nested) {
                return null;
            }

            $skip = true;
            return null;
        }

        return $value;
    }

    /**
     * Normalize array contents recursively.
     *
     * @param array<array-key, mixed> $value
     *
     * @return array<array-key, mixed>
     */
    protected static function normalizeNestedArray(array $value): array
    {
        foreach ($value as $k => $v) {
            $skip = false;
            $value[$k] = static::normalizeValue($v, $skip, true);
            if ($skip) {
                $value[$k] = null;
            }
        }

        return $value;
    }
}
