<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Input contract that exposes positional (non-named) arguments.
 *
 * Guidance: Implement on CLI and other transports that support ordered positional binding;
 * HTTP request input typically does not.
 *
 * @see \Switon\Core\InputInterface
 * @see \Switon\Cli\OptionsInterface
 * @see \Switon\Binding\ScalarResolver
 */
interface PositionalInputInterface
{
    /**
     * @return list<string>
     */
    public function getPositional(): array;
}
