<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\Attribute\Autowired;
use Switon\Core\Exception\MisuseException;
use Switon\Core\Exception\NotSupportedException;
use Switon\Core\Random;
use Switon\Core\RandomInterface;
use Switon\Core\Tests\TestCase;
use ValueError;

/**
 * Test cases for Random class.
 *
 * Tests cryptographically secure random number generation.
 */
class RandomTest extends TestCase
{
    #[Autowired] protected RandomInterface $random;

    /**
     * Test that Random implements RandomInterface.
     */
    public function testRandomImplementsRandomInterface(): void
    {
        $this->assertInstanceOf(RandomInterface::class, $this->random);
    }

    /**
     * Test that bytes() returns string of correct length.
     */
    public function testBytesReturnsCorrectLength(): void
    {
        $bytes = $this->random->bytes(16);

        $this->assertIsString($bytes);
        $this->assertSame(16, strlen($bytes));
    }

    /**
     * Test that bytes() returns different values on consecutive calls.
     */
    public function testBytesReturnsDifferentValues(): void
    {
        $bytes1 = $this->random->bytes(16);
        $bytes2 = $this->random->bytes(16);

        $this->assertNotSame($bytes1, $bytes2);
    }

    /**
     * Test that bytes() works with various lengths.
     */
    public function testBytesWorksWithVariousLengths(): void
    {
        $lengths = [1, 16, 32, 64];

        foreach ($lengths as $length) {
            $bytes = $this->random->bytes($length);
            $this->assertSame($length, \strlen($bytes), "Failed for length: $length");
        }
    }

    /**
     * Test that int() works with negative ranges.
     */
    public function testIntWorksWithNegativeRanges(): void
    {
        $value = $this->random->int(-100, -10);

        $this->assertIsInt($value);
        $this->assertGreaterThanOrEqual(-100, $value);
        $this->assertLessThanOrEqual(-10, $value);
    }

    /**
     * Test that int() works with mixed negative and positive ranges.
     */
    public function testIntWorksWithMixedRanges(): void
    {
        $value = $this->random->int(-50, 50);

        $this->assertIsInt($value);
        $this->assertGreaterThanOrEqual(-50, $value);
        $this->assertLessThanOrEqual(50, $value);
    }

    /**
     * Test that int() works when min equals max.
     */
    public function testIntWorksWhenMinEqualsMax(): void
    {
        $value = $this->random->int(42, 42);

        $this->assertSame(42, $value);
    }

    public function testIntThrowsValueErrorWhenMinGreaterThanMax(): void
    {
        $this->expectException(ValueError::class);
        $this->random->int(10, 1);
    }

    /**
     * Test that bytes() with length 1 returns single byte.
     */
    public function testBytesWithLengthOneReturnsSingleByte(): void
    {
        $bytes = $this->random->bytes(1);

        $this->assertSame(1, strlen($bytes));
    }

    /**
     * Test that Random can be instantiated multiple times.
     */
    public function testRandomCanBeInstantiatedMultipleTimes(): void
    {
        $random1 = new Random();
        $random2 = new Random();

        $bytes1 = $random1->bytes(16);
        $bytes2 = $random2->bytes(16);

        // Different instances should produce different random values
        $this->assertNotSame($bytes1, $bytes2);
    }

    public function testUuidMatchesFormat(): void
    {
        $random = new Random();
        $uuid = $random->uuid();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }

    public function testCharsEmptyLengthReturnsEmptyString(): void
    {
        $random = new Random();

        $this->assertSame('', $random->chars(0, 62));
    }

    public function testCharsNegativeLengthRaisesMisuseException(): void
    {
        $random = new Random();

        $this->expectException(MisuseException::class);
        $random->chars(-1);
    }

    public function testCharsBase16EvenLengthUsesHex(): void
    {
        $random = new Random();
        $s = $random->chars(8, 16);

        $this->assertSame(8, strlen($s));
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $s);
    }

    public function testCharsBase16OddLengthTruncatesHex(): void
    {
        $random = new Random();
        $s = $random->chars(7, 16);

        $this->assertSame(7, strlen($s));
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $s);
    }

    public function testCharsBase62UsesSubstitutedAlphabet(): void
    {
        $random = new Random();
        $s = $random->chars(32, 62);

        $this->assertSame(32, strlen($s));
        $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]+$/', $s);
    }

    public function testCharsBaseInRangeUsesAlphanumericPool(): void
    {
        $random = new Random();
        $s = $random->chars(40, 36);

        $this->assertSame(40, strlen($s));
        $this->assertMatchesRegularExpression('/^[0-9a-z]+$/', $s);
    }

    public function testCharsBaseBetween37And61CanEmitUppercaseLetters(): void
    {
        $random = new Random();
        $hadUpper = false;

        for ($i = 0; $i < 400; $i++) {
            $s = $random->chars(48, 48);
            $this->assertSame(48, strlen($s));
            if (preg_match('/[A-Z]/', $s) === 1) {
                $hadUpper = true;

                break;
            }
        }

        $this->assertTrue($hadUpper, 'Alphabet branch for r >= 36 should surface uppercase letters');
    }

    public function testCharsUnsupportedBaseRaisesNotSupportedException(): void
    {
        $random = new Random();

        $this->expectException(NotSupportedException::class);
        $random->chars(4, 99);
    }

    public function testCharsBaseOneRaisesNotSupportedException(): void
    {
        $random = new Random();

        $this->expectException(NotSupportedException::class);
        $random->chars(4, 1);
    }
}
