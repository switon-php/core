<?php

declare(strict_types=1);

namespace Switon\Core;

use Swoole\Coroutine as SwooleCoroutine;

use function array_shift;
use function debug_backtrace;

/**
 * Normalized stack traces across coroutine and non-coroutine runtimes.
 *
 * @see \Swoole\Coroutine::getBackTrace()
 * @see debug_backtrace()
 */
class Backtrace
{
    /** @return array<int, array<string, mixed>> */
    public static function get(int $options, int $limit = 0): array
    {
        if (Runtime::isCoroutineEnabled()) {
            // Swoole's getBackTrace includes an extra Swoole internal frame at top
            $traces = SwooleCoroutine::getBackTrace(0, $options, $limit > 0 ? $limit + 1 : 0);
            array_shift($traces); // Remove Swoole internal frame
        } else {
            $traces = debug_backtrace($options, $limit);
        }
        array_shift($traces); // Remove Backtrace::get() itself

        return $traces;
    }
}
