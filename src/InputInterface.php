<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Unified input-access contract for HTTP, CLI, and custom transports.
 *
 * Guidance: Use this for raw transport values only; typed binding and positional semantics live in higher-level contracts.
 *
 * @see \Switon\Cli\OptionsInterface
 * @see \Switon\Core\PositionalInputInterface
 * @see \Switon\Http\RequestInterface
 * @see \Switon\Cli\Options
 */
interface InputInterface
{
    /**
     * Check whether a key exists.
     *
     * @param string|int $name Input key
     *
     * @return bool True when the key exists
     */
    public function has(string|int $name): bool;

    /**
     * Get a value by key.
     *
     * @param string|int $name Input key
     * @param mixed $default Returned when key does not exist
     *
     * @return mixed Input value or default
     */
    public function get(string|int $name, mixed $default = null): mixed;

    /**
     * Return all input values.
     *
     * @return array<string, mixed>
     *
     * @see \Switon\Binding\ArgumentsBinder::resolve()
     * @see \Switon\Binding\ScalarResolver
     * @see \Switon\Http\RequestInterface::all()
     */
    public function all(): array;
}
