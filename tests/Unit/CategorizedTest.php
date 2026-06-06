<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Stringable;
use Switon\Core\Categorizable;
use Switon\Core\Categorized;
use Switon\Core\Tests\TestCase;

/**
 * Test cases for Categorized class.
 *
 * Tests categorized content container with category and content.
 */
class CategorizedTest extends TestCase
{
    /**
     * Test that Categorized::of() creates instance with category and content.
     */
    public function testCategorizedOfCreatesInstance(): void
    {
        $categorized = Categorized::of('database', 'Connection established');

        $this->assertInstanceOf(Categorized::class, $categorized);
        $this->assertSame('database', $categorized->getCategory());
        $this->assertSame('Connection established', (string)$categorized);
    }

    /**
     * Test that Categorized can be used as string.
     */
    public function testCategorizedCanBeUsedAsString(): void
    {
        $categorized = Categorized::of('api', 'Request processed');

        $this->assertSame('Request processed', (string)$categorized);
        $this->assertSame('Request processed', $categorized->__toString());
    }

    /**
     * Test that Categorized accepts empty content.
     */
    public function testCategorizedAcceptsEmptyContent(): void
    {
        $categorized = Categorized::of('info');

        $this->assertSame('info', $categorized->getCategory());
        $this->assertSame('', (string)$categorized);
    }

    /**
     * Test that Categorized accepts Stringable content.
     */
    public function testCategorizedAcceptsStringableContent(): void
    {
        $stringable = new class () implements Stringable {
            public function __toString(): string
            {
                return 'Stringable content';
            }
        };

        $categorized = Categorized::of('test', $stringable);

        $this->assertSame('test', $categorized->getCategory());
        $this->assertSame('Stringable content', (string)$categorized);
    }

    /**
     * Test that Categorized implements Categorizable interface.
     */
    public function testCategorizedImplementsCategorizable(): void
    {
        $categorized = Categorized::of('category', 'content');

        $this->assertInstanceOf(Categorizable::class, $categorized);
    }
}
