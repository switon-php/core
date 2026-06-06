<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\SceneManagerInterface;
use Switon\Core\Tests\TestCase;

/**
 * Test cases for SceneManager class.
 *
 * Tests runtime scene state and service binding behavior.
 */
class SceneManagerTest extends TestCase
{
    public function testSceneManagerUsesDefaultScene(): void
    {
        /** @var SceneManagerInterface $sceneManager */
        $sceneManager = $this->container->get(SceneManagerInterface::class);

        $this->assertSame('default', $sceneManager->getScene());
    }

    public function testSceneManagerCanSwitchScene(): void
    {
        /** @var SceneManagerInterface $sceneManager */
        $sceneManager = $this->container->get(SceneManagerInterface::class);

        $sceneManager->setScene('cli');

        $this->assertSame('cli', $sceneManager->getScene());
    }

    public function testSceneManagerResetsEmptySceneToDefault(): void
    {
        /** @var SceneManagerInterface $sceneManager */
        $sceneManager = $this->container->get(SceneManagerInterface::class);

        $sceneManager->setScene('');

        $this->assertSame('default', $sceneManager->getScene());
    }

    public function testSceneManagerKeepsWhitespaceOnlySceneAsExplicitValue(): void
    {
        /** @var SceneManagerInterface $sceneManager */
        $sceneManager = $this->container->get(SceneManagerInterface::class);

        $sceneManager->setScene('   ');

        $this->assertSame('   ', $sceneManager->getScene());
    }
}
