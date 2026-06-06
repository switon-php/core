<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\Naming;
use Switon\Core\Tests\TestCase;

/**
 * Test cases for Naming class.
 *
 * Tests string naming convention conversions between camelCase, PascalCase,
 * snake_case, and kebab-case formats.
 */
class NamingTest extends TestCase
{
    /**
     * Test that camel() converts various formats to camelCase.
     */
    public function testCamelConvertsToCamelCase(): void
    {
        $this->assertSame('fooBar', Naming::camel('foo-bar'));
        $this->assertSame('fooBar', Naming::camel('foo_bar'));
        $this->assertSame('fOOBAR', Naming::camel('FOO_BAR'));
        $this->assertSame('fooBar', Naming::camel('foo bar'));
        $this->assertSame('fooBar', Naming::camel('FooBar'));
        $this->assertSame('xMLParser', Naming::camel('XMLParser'));
        $this->assertSame('getUserId', Naming::camel('get-user-id'));
    }

    /**
     * Test that snake() converts various formats to snake_case.
     */
    public function testSnakeConvertsToSnakeCase(): void
    {
        $this->assertSame('foo_bar', Naming::snake('fooBar'));
        $this->assertSame('xml_parser', Naming::snake('XMLParser'));
        $this->assertSame('https_connection', Naming::snake('HTTPSConnection'));
        $this->assertSame('get_user_id', Naming::snake('getUserID'));
    }

    /**
     * Test that pascal() converts various formats to PascalCase.
     */
    public function testPascalConvertsToPascalCase(): void
    {
        $this->assertSame('FooBar', Naming::pascal('foo-bar'));
        $this->assertSame('FooBar', Naming::pascal('foo_bar'));
        $this->assertSame('FooBar', Naming::pascal('fooBar'));
        $this->assertSame('FOOBAR', Naming::pascal('FOO_BAR'));
        $this->assertSame('XmlParser', Naming::pascal('xml-parser'));
    }

    /**
     * Test that kebab() converts various formats to kebab-case.
     */
    public function testKebabConvertsToKebabCase(): void
    {
        $this->assertSame('foo-bar', Naming::kebab('fooBar'));
        $this->assertSame('xml-parser', Naming::kebab('XMLParser'));
        $this->assertSame('https-connection', Naming::kebab('HTTPSConnection'));
        $this->assertSame('get-user-id', Naming::kebab('getUserID'));
    }

    /**
     * Test that plural() converts singular words to plural form.
     */
    public function testPluralConvertsToPlural(): void
    {
        // Basic conversions
        $this->assertSame('users', Naming::plural('user'));
        $this->assertSame('products', Naming::plural('product'));
        $this->assertSame('orders', Naming::plural('order'));

        // y → ies
        $this->assertSame('categories', Naming::plural('category'));
        $this->assertSame('companies', Naming::plural('company'));

        // es endings
        $this->assertSame('classes', Naming::plural('class'));
        $this->assertSame('boxes', Naming::plural('box'));
        $this->assertSame('addresses', Naming::plural('address'));

        // z and o endings (new support)
        $this->assertSame('buzzes', Naming::plural('buzz'));
        $this->assertSame('heroes', Naming::plural('hero'));

        // Case preservation
        $this->assertSame('Users', Naming::plural('User'));
        $this->assertSame('Categories', Naming::plural('Category'));
        $this->assertSame('USERS', Naming::plural('USER'));
        $this->assertSame('CATEGORIES', Naming::plural('CATEGORY'));

        // Short words (unchanged)
        $this->assertSame('id', Naming::plural('id'));
        $this->assertSame('ab', Naming::plural('ab'));
    }

    /**
     * Test that singular() converts plural words to singular form.
     */
    public function testSingularConvertsToSingular(): void
    {
        // Basic conversions
        $this->assertSame('user', Naming::singular('users'));
        $this->assertSame('product', Naming::singular('products'));
        $this->assertSame('order', Naming::singular('orders'));

        // ies → y
        $this->assertSame('category', Naming::singular('categories'));
        $this->assertSame('company', Naming::singular('companies'));

        // es → (remove es or only s depending on context)
        $this->assertSame('class', Naming::singular('classes'));
        $this->assertSame('box', Naming::singular('boxes'));
        $this->assertSame('address', Naming::singular('addresses'));

        // z and o endings (new support)
        $this->assertSame('buzz', Naming::singular('buzzes'));
        $this->assertSame('hero', Naming::singular('heroes'));

        // es ending but should only remove 's' (key fix)
        $this->assertSame('role', Naming::singular('roles'));

        // ch and sh endings
        $this->assertSame('dish', Naming::singular('dishes'));
        $this->assertSame('branch', Naming::singular('branches'));

        // Case preservation
        $this->assertSame('User', Naming::singular('Users'));
        $this->assertSame('Category', Naming::singular('Categories'));
        $this->assertSame('USER', Naming::singular('USERS'));
        $this->assertSame('CATEGORY', Naming::singular('CATEGORIES'));

        // Short words (unchanged)
        $this->assertSame('id', Naming::singular('id'));
        $this->assertSame('ab', Naming::singular('ab'));

        // Words that are already singular (unchanged)
        $this->assertSame('user', Naming::singular('user'));
        $this->assertSame('category', Naming::singular('category'));
    }

    /**
     * Test plural and singular round-trip conversions.
     */
    public function testPluralSingularRoundTrip(): void
    {
        $words = ['user', 'product', 'category', 'company', 'class', 'box', 'address', 'buzz', 'hero'];

        foreach ($words as $word) {
            $plural = Naming::plural($word);
            $singular = Naming::singular($plural);
            $this->assertSame($word, $singular, "Round-trip failed for: $word → $plural → $singular");
        }
    }

}
