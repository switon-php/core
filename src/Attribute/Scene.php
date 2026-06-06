<?php

declare(strict_types=1);

namespace Switon\Core\Attribute;

use Attribute;

/**
 * Marks a bootstrap entry class with its runtime scene.
 *
 * @see \Switon\Core\SceneManager
 * @see \Switon\Kernel\Kernel
 * @see \Switon\Cli\Handler
 * @see \Switon\Schedule\Command\ScheduleCommand
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Scene
{
    public function __construct(public string $name)
    {
    }
}
