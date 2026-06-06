<?php

declare(strict_types=1);

namespace Switon\Core\Exception;

use Switon\Core\Exception;

/**
 * Signals cloning attempts on non-cloneable objects.
 *
 * @see \Switon\Core\Exception
 * @see \Switon\HttpClient\HttpClient::__clone()
 */
class NonCloneableException extends Exception
{
}
