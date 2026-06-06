<?php

declare(strict_types=1);

namespace Switon\Core;

use Switon\Core\Exception\MisuseException;
use Switon\Core\Exception\NotSupportedException;

use function base64_encode;
use function bin2hex;
use function ceil;
use function chr;
use function ord;
use function random_bytes;
use function random_int;
use function sprintf;
use function strtr;
use function substr;

/**
 * Cryptographically secure random generator.
 *
 * Uses PHP secure primitives (`random_bytes`, `random_int`) and implements `RandomInterface` for testable substitution.
 *
 * @see \Switon\Core\RandomInterface
 */
class Random implements RandomInterface
{
    /**
     * {@inheritDoc}
     */
    public function bytes(int $length): string
    {
        return random_bytes($length);
    }

    /**
     * {@inheritDoc}
     */
    public function int(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    /**
     * {@inheritDoc}
     */
    public function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function chars(int $length, int $base = 62): string
    {
        if ($length < 0) {
            MisuseException::raise('Length cannot be negative, got {length}.', ['length' => $length]);
        } elseif ($length === 0) {
            return '';
        } elseif ($base === 16) {
            if ($length % 2 === 0) {
                return bin2hex(random_bytes($length / 2));
            } else {
                return substr(bin2hex(random_bytes((int)ceil($length / 2))), 0, $length);
            }
        } elseif ($base === 62) {
            $str = base64_encode(random_bytes((int)ceil($length * 0.75)));
            $str = strtr($str, ['+' => '0', '/' => '5', '=' => '9']);
            return substr($str, 0, $length);
        } elseif ($base > 1 && $base <= 62) {
            $str = '';
            $bytes = random_bytes($length);
            for ($i = 0; $i < $length; $i++) {
                $r = ord($bytes[$i]) % $base;
                if ($r < 10) {
                    $str .= chr(ord('0') + $r);
                } elseif ($r < 36) {
                    $str .= chr(ord('a') + $r - 10);
                } else {
                    $str .= chr(ord('A') + $r - 36);
                }
            }
            return $str;
        } else {
            NotSupportedException::raise('Base {base} not supported, valid range: 2-62', ['base' => $base]);
        }
    }
}
