<?php

declare(strict_types=1);

namespace Switon\Core\PhpStan;

use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Switon\Core\Lazy;

/**
 * Strips the Lazy marker from union types used by #[Autowired] Type|Lazy properties.
 */
final class LazyUnionTypeHelper
{
    public static function withoutLazy(Type $type): Type
    {
        if (!$type instanceof UnionType) {
            return $type;
        }

        $kept = [];
        foreach ($type->getTypes() as $member) {
            if (self::isLazyType($member)) {
                continue;
            }
            $kept[] = $member;
        }

        if ($kept === []) {
            return $type;
        }

        if (count($kept) === 1) {
            return $kept[0];
        }

        return new UnionType($kept);
    }

    public static function containsLazy(Type $type): bool
    {
        if (!$type instanceof UnionType) {
            return self::isLazyType($type);
        }

        foreach ($type->getTypes() as $member) {
            if (self::isLazyType($member)) {
                return true;
            }
        }

        return false;
    }

    private static function isLazyType(Type $type): bool
    {
        return $type->getObjectClassNames() === [Lazy::class];
    }
}
