<?php

declare(strict_types=1);

namespace Switon\Core\Exception;

/**
 * Signals access to an undefined path alias.
 *
 * @see \Switon\Core\Exception\RuntimeException
 * @see \Switon\Core\PathAlias::get()
 */
class AliasNotFoundException extends RuntimeException
{
}
