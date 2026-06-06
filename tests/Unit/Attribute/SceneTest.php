<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit\Attribute;

use Switon\Core\Attribute\Scene;
use Switon\Core\Tests\TestCase;

final class SceneTest extends TestCase
{
    public function testAttributeExposesSceneChannelName(): void
    {
        $scene = new Scene('http');

        $this->assertSame('http', $scene->name);
    }
}
