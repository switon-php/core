<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Array output contract for JSON-safe structured data.
 *
 * Guidance: Implement this interface only when the object has a stable array representation; use `JsonSerializable` for scalar or mixed JSON-safe output.
 *
 * @see \Switon\Core\Json
 * @see \Switon\Orm\Entity
 */
interface ArrayableInterface
{
    /**
     * Convert the object to an array payload.
     *
     * @return array<string, mixed>|array<int, mixed>
     */
    public function toArray(): array;
}
