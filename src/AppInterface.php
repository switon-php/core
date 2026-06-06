<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Application metadata contract.
 *
 * Use for app identity, version, environment, debug, and timezone reads.
 * Runtime scene and container access are outside this interface.
 *
 * Guidance: Keep this interface metadata-only; service resolution and runtime state belong to other contracts.
 *
 * Road-signs:
 * - id/name/env/debug/timezone metadata only
 * - runtime scene belongs to <code>SceneManagerInterface</code>
 *
 * @see \Switon\Core\App
 * @see \Switon\Core\SceneManagerInterface
 */
interface AppInterface
{
    /**
     * Get application identifier.
     */
    public function id(): string;

    /**
     * Get application display name.
     */
    public function name(): string;

    /**
     * Get application version.
     */
    public function version(): string;

    /**
     * Get environment name (e.g., <code>prod</code>, <code>dev</code>, <code>test</code>).
     */
    public function env(): string;

    /**
     * Check if current environment matches the given environment name.
     */
    public function isEnv(string $env): bool;

    /**
     * Check if debug mode is enabled.
     */
    public function isDebug(): bool;

    /**
     * Get application timezone (e.g., "UTC", "Asia/Shanghai").
     */
    public function timezone(): string;
}
