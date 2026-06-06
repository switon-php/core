<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Exception for intentional flow interruption.
 *
 * Use for expected short-circuit paths such as auth stops, cache hits, redirects, or preflight exits.
 *
 * @see \Switon\Eventing\EventDispatcher
 * @see \Switon\Cli\Server
 * @see \Switon\Http\ExceptionDispatcher
 */
class StopFlow extends Exception
{
    /**
     * Create instance with explicit reason.
     *
     * @param array<string, mixed> $context Additional context data
     */
    public static function because(string $reason, array $context = []): static
    {
        return new static($reason, $context);
    }

    /**
     * Create instance with default abort reason.
     *
     * @param array<string, mixed> $context Additional context data
     */
    public static function abort(array $context = []): static
    {
        return new static('Process aborted', $context);
    }
}
