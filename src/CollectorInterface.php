<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Published data-collector protocol for diagnostics and observability packages.
 *
 * @see \Switon\Inspector\BasicCollector
 * @see \Switon\Inspector\Collector\AppCollector
 */
interface CollectorInterface
{
    /**
     * Initialize collector lifecycle hooks.
     */
    public function boot(): void;

    /**
     * Return collected diagnostics for current lifecycle.
     *
     * @return array<string, mixed>|array<int, mixed>|string
     */
    public function collect(): array|string;
}
