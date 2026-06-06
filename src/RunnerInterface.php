<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Published payload-execution protocol for task, queue, RPC, and executor packages.
 */
interface RunnerInterface
{
    /**
     * Execute this runner with caller-provided payload.
     */
    public function run(mixed $payload): mixed;
}
