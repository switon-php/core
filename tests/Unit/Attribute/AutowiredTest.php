<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit\Attribute;

use Switon\Core\Attribute\Autowired;
use Switon\Core\Tests\TestCase;

/**
 * Test cases for Autowired attribute class.
 *
 * Tests attribute instantiation, property access, and basic functionality.
 */
class AutowiredTest extends TestCase
{
    /**
     * Test that Autowired attribute can be instantiated with default value.
     */
    public function testAutowiredWithDefaultValue(): void
    {
        $attribute = new Autowired();

        $this->assertFalse($attribute->isInstances());
    }

    /**
     * Test that Autowired attribute can be instantiated with instances=true.
     */
    public function testAutowiredWithInstancesTrue(): void
    {
        $attribute = new Autowired(instances: true);

        $this->assertTrue($attribute->isInstances());
    }

    /**
     * Test that Autowired attribute can be instantiated with instances=false.
     */
    public function testAutowiredWithInstancesFalse(): void
    {
        $attribute = new Autowired(instances: false);

        $this->assertFalse($attribute->isInstances());
    }
}
