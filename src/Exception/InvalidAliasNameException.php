<?php

declare(strict_types=1);

namespace Switon\Core\Exception;

/**
 * Signals invalid path alias names.
 *
 * @see \Switon\Core\Exception\RuntimeException
 * @see \Switon\Core\PathAlias::set()
 */
class InvalidAliasNameException extends RuntimeException
{
}
