<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit\Attribute;

use Switon\Core\Attribute\Communal;
use Switon\Core\Tests\TestCase;
use Attribute;
use ReflectionClass;

/**
 * Test cases for Communal attribute.
 *
 * Tests the Communal attribute marker functionality.
 */
class CommunalTest extends TestCase
{
    public function testCommunalAttributeCanBeInstantiated(): void
    {
        $attribute = new Communal();
        $this->assertInstanceOf(Communal::class, $attribute);
    }

    public function testCommunalAttributeIsTargetProperty(): void
    {
        $reflection = new ReflectionClass(Communal::class);
        $attributes = $reflection->getAttributes();

        $attributeAttribute = null;
        foreach ($attributes as $attr) {
            if ($attr->getName() === Attribute::class) {
                $attributeAttribute = $attr;
                break;
            }
        }

        $this->assertNotNull($attributeAttribute, 'Communal should have Attribute attribute');

        $instance = $attributeAttribute->newInstance();
        $this->assertSame(Attribute::TARGET_PROPERTY, $instance->flags);
    }

    public function testCommunalAttributeOnProperty(): void
    {
        $testClass = new class () {
            #[Communal]
            public string $sharedConfig = 'test';

            #[Communal]
            public array $cache = [];
        };

        $reflection = new ReflectionClass($testClass);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $attributes = $property->getAttributes(Communal::class);
            $this->assertCount(1, $attributes, "Property {$property->getName()} should have Communal attribute");

            $communal = $attributes[0]->newInstance();
            $this->assertInstanceOf(Communal::class, $communal);
        }
    }
}
