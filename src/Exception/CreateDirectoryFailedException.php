<?php

declare(strict_types=1);

namespace Switon\Core\Exception;

/**
 * Signals directory creation failures.
 *
 * @see \Switon\Core\Exception\RuntimeException
 * @see \Switon\Core\Filesystem::mkdir()
 */
class CreateDirectoryFailedException extends RuntimeException
{
}
