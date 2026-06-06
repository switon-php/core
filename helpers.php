<?php

declare(strict_types=1);

use Switon\Core\App;
use Switon\Core\Json;

if (!function_exists('make')) {
    /**
     * Create an instance through the container.
     *
     * @template T of object
     *
     * @param class-string<T> $name Class name to instantiate
     * @param array<string, mixed> $parameters Constructor or autowired parameter overrides
     *
     * @phpstan-return T
     *
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    function make(string $name, array $parameters = []): mixed
    {
        return App::make($name, $parameters);
    }
}

if (!function_exists('env')) {
    /**
     * Read an environment variable, casting by the default value when useful.
     *
     * @param string $key Environment variable name
     * @param mixed $default Fallback value; bool, int, and float defaults also control casting
     *
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        // Type conversion based on default value
        if (is_bool($default)) {
            $lower = strtolower($value);
            return in_array($lower, ['1', 'on', 'true', 'yes'], true);
        } elseif (is_int($default)) {
            return (int)$value;
        } elseif (is_float($default)) {
            return (float)$value;
        }

        return $value;
    }
}

if (!function_exists('console_log')) {
    /**
     * Write a formatted log line to STDERR.
     *
     * @param string $level Log level label
     * @param string|\Stringable $message Message text; `{key}` placeholders are replaced from context
     * @param array<string, mixed> $context Placeholder values
     */
    function console_log(string $level, string|\Stringable $message, array $context = []): void
    {
        if (defined('STDERR')) {
            if (is_string($message) && $context !== [] && str_contains($message, '{')) {
                $replaces = [];
                foreach ($context as $key => $value) {
                    $replaces["{{$key}}"] = is_string($value) ? $value : Json::stringify($value);
                }
                $message = strtr($message, $replaces);
            }
            fprintf(STDERR, '[%s][%s]: %s', date('c'), $level, $message);
            fprintf(STDERR, PHP_EOL);
        }
    }
}

if (!function_exists('json')) {
    /**
     * Encode a value to JSON with framework defaults.
     *
     * @param mixed $value Value to encode
     * @param int $options Additional JSON encoding options
     *
     * @throws \Switon\Core\Exception\JsonException
     */
    function json(mixed $value, int $options = 0): string
    {
        return Json::stringify($value, $options);
    }
}

if (!function_exists('array_first')) {
    /**
     * Return the first array value without changing the internal pointer.
     *
     * @param array<array-key, mixed> $array Source array
     *
     * @return mixed
     */
    function array_first(array $array): mixed
    {
        $key = array_key_first($array);

        return $key !== null ? $array[$key] : null;
    }
}

if (!function_exists('array_last')) {
    /**
     * Return the last array value without changing the internal pointer.
     *
     * @param array<array-key, mixed> $array Source array
     *
     * @return mixed
     */
    function array_last(array $array): mixed
    {
        $key = array_key_last($array);

        return $key !== null ? $array[$key] : null;
    }
}
