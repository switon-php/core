<?php

declare(strict_types=1);

namespace Switon\Core;

use JsonSerializable;
use Stringable;
use Throwable;

use function dirname;
use function implode;
use function is_array;
use function is_object;
use function is_scalar;
use function is_string;
use function preg_match_all;
use function preg_replace;
use function realpath;
use function strtr;

/**
 * Generic string helpers shared across components.
 *
 * @see \Switon\Core\ClassName
 * @see \Switon\Core\Naming
 */
class Strings
{
    /**
     * Join elements into a chain string.
     *
     * @param array<string> $elements
     */
    public static function chain(array $elements, string $separator = ' -> ', ?string $append = null): string
    {
        if ($append !== null) {
            $elements[] = $append;
        }

        return implode($separator, $elements);
    }

    /**
     * Replace `{key}` placeholders with context values.
     *
     * @param array<string, mixed> $context
     */
    public static function interpolate(string|Stringable $message, array $context): string
    {
        $replaces = [];
        $messageStr = (string)$message;
        preg_match_all('#{([\w.]+)}#', $messageStr, $matches);
        foreach ($matches[1] as $key) {
            if (($val = $context[$key] ?? null) === null) {
                continue;
            }

            if (is_string($val)) {
                // no-op
            } elseif ($val instanceof JsonSerializable) {
                $val = Json::stringify($val);
            } elseif ($val instanceof Stringable) {
                $val = (string)$val;
            } elseif (is_scalar($val)) {
                $val = Json::stringify($val);
            } elseif (is_array($val)) {
                $val = Json::stringify($val);
            } elseif (is_object($val)) {
                $val = Json::stringify((array)$val);
            } else {
                continue;
            }

            $replaces['{' . $key . '}'] = $val;
        }
        return strtr($messageStr, $replaces);
    }

    /**
     * Render exception and previous chain for logs.
     */
    public static function renderException(Throwable $exception, ?string $root = null): string
    {
        $str = $exception::class . ': ' . $exception->getMessage() . PHP_EOL;
        $str .= '    at ' . $exception->getFile() . ':' . $exception->getLine() . PHP_EOL;
        $traces = $exception->getTraceAsString();
        $str .= preg_replace('/#\d+\s/', '    at ', $traces);

        $prev = $traces;
        $caused = $exception;
        while ($caused = $caused->getPrevious()) {
            $str .= PHP_EOL . '  Caused by ' . $caused::class . ': ' . $caused->getMessage() . PHP_EOL;
            $str .= '    at ' . $caused->getFile() . ':' . $caused->getLine() . PHP_EOL;
            $traces = $caused->getTraceAsString();
            if ($traces !== $prev) {
                $str .= preg_replace('/#\d+\s/', '    at ', $traces);
            } else {
                $str .= '    at ...';
            }
            $prev = $traces;
        }

        if ($root !== null && ($realRoot = realpath($root)) !== false) {
            $str = strtr($str, [dirname($realRoot) . '/' => '']);
        }

        return $str;
    }
}
