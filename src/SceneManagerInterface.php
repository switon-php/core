<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Contract for reading and writing the current runtime scene.
 *
 * Use for bootstrap and infrastructure boundaries only.
 *
 * @see \Switon\Core\SceneManager
 * @see \Switon\Core\AppInterface
 * @see \Switon\Core\Attribute\Scene
 */
interface SceneManagerInterface
{
    /**
     * Get current runtime scene.
     */
    public function getScene(): string;

    /**
     * Set current runtime scene (e.g., <code>cli</code>, <code>http</code>, <code>schedule</code>).
     */
    public function setScene(string $scene): void;
}
