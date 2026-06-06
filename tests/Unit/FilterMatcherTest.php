<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\FilterMatcher;
use Switon\Core\Tests\TestCase;

class FilterMatcherTest extends TestCase
{
    public function testEmptyFilterMatchesEverything(): void
    {
        $matcher = new FilterMatcher();

        $this->assertTrue($matcher->match('', 'anything'));
        $this->assertTrue($matcher->match('   ', 'anything'));
        $this->assertTrue($matcher->matchAny('', ['a' => 'x']));
        $this->assertTrue($matcher->matchAny('   ', ['a' => 'x']));
    }

    public function testMatchAnyReturnsTrueOnFirstMatch(): void
    {
        $matcher = new FilterMatcher();

        $this->assertTrue($matcher->matchAny('foo', ['a' => 'bar', 'b' => 'FOO', 'c' => 'baz']));
    }

    public function testMatchAnyReturnsFalseWhenNoSubjectsMatch(): void
    {
        $matcher = new FilterMatcher();

        $this->assertFalse($matcher->matchAny('foo', ['a' => 'bar', 'b' => 'baz']));
    }

    public function testMatchAnySupportsWildcards(): void
    {
        $matcher = new FilterMatcher();

        $this->assertTrue($matcher->matchAny('user.*', ['x' => 'order.created', 'y' => 'user.created']));
        $this->assertTrue($matcher->matchAny('user.?reated', ['x' => 'user.created']));
        $this->assertTrue($matcher->matchAny('user.?reated', ['x' => 'user.createdx'])); // unanchored match
        $this->assertFalse($matcher->matchAny('user.?reated', ['x' => 'user.crated'])); // missing one character
    }

    public function testMatchAnyRecursesIntoNestedArrays(): void
    {
        $matcher = new FilterMatcher();

        $this->assertTrue($matcher->matchAny('user.*', [
            'event' => [
                'class' => 'Switon\\Demo\\UserCreated',
                'category' => 'user.created',
            ],
        ]));
    }

    public function testMatchAnyIgnoresNumericKeys(): void
    {
        $matcher = new FilterMatcher();

        $this->assertFalse($matcher->matchAny('foo', ['foo']));
        $this->assertTrue($matcher->matchAny('foo', ['a' => 'foo']));
    }

    public function testMatchCaseInsensitiveSubstringWithoutWildcards(): void
    {
        $matcher = new FilterMatcher();

        $this->assertTrue($matcher->match('Hello', 'Say Hello World'));
        $this->assertFalse($matcher->match('missing', 'Say Hello World'));
    }

    public function testMatchWildcardPattern(): void
    {
        $matcher = new FilterMatcher();

        $this->assertTrue($matcher->match('foo.*', 'foo.bar'));
        $this->assertTrue($matcher->match('pre.?ost', 'pre.post'));
    }

    public function testMatchAnyWithStringSubjectDelegatesToMatch(): void
    {
        $matcher = new FilterMatcher();

        $this->assertTrue($matcher->matchAny('bar', 'foo bar baz'));
        $this->assertFalse($matcher->matchAny('missing', 'foo bar baz'));
    }

    /**
     * Only string leaves participate in matching; numeric or other non-string values are ignored (no false positives).
     */
    public function testMatchAnyIgnoresNonStringLeafValues(): void
    {
        $matcher = new FilterMatcher();

        $this->assertFalse($matcher->matchAny('needle', ['a' => 42, 'b' => true]));
        $this->assertFalse($matcher->matchAny('needle', ['nested' => ['k' => 7]]));
    }
}
