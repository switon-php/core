<?php

declare(strict_types=1);

namespace Switon\Core\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Switon\Core\App;
use Switon\Core\ConsoleInterface;
use Switon\Core\ContextManagerInterface;
use Switon\Core\InjectorInterface;
use Switon\Core\Tests\Support\BufferedConsole;
use Switon\Core\Tests\Support\InMemoryContextManager;
use Switon\Core\Tests\Support\SimpleContainer;

abstract class TestCase extends BaseTestCase
{
    protected SimpleContainer $container;
    protected InjectorInterface $injector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new SimpleContainer();
        $this->injector = $this->container->get(InjectorInterface::class);
        App::setContainer($this->container);

        $console = new BufferedConsole();

        $this->container->set(ContextManagerInterface::class, new InMemoryContextManager());
        $this->container->set(ConsoleInterface::class, $console);
        $this->container->set(BufferedConsole::class, $console);

        $this->setUpContainer();
        $this->injector->inject($this);
    }

    protected function tearDown(): void
    {
        App::setContainer(null);
        parent::tearDown();
    }

    protected function setUpContainer(): void
    {
    }

    public function make(string $name, array $parameters = []): mixed
    {
        return $this->container->make($name, $parameters);
    }
}
