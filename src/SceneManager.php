<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Stores the current runtime scene for bootstrap and infrastructure code.
 *
 * @see \Switon\Core\SceneManagerInterface
 * @see \Switon\Core\AppInterface
 * @see \Switon\Core\Attribute\Scene
 */
class SceneManager implements SceneManagerInterface
{
    protected string $scene = 'default';

    public function getScene(): string
    {
        return $this->scene;
    }

    public function setScene(string $scene): void
    {
        $this->scene = $scene !== '' ? $scene : 'default';
    }
}
