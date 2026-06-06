<?php

declare(strict_types=1);

namespace Switon\Core\Exception;

/**
 * Signals circular references in path alias resolution.
 *
 * @see \Switon\Core\Exception\RuntimeException
 * @see \Switon\Core\PathAlias::set()
 */
class CircularAliasException extends RuntimeException
{
}
