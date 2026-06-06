<?php

declare(strict_types=1);

namespace Switon\Core\Exception;

use Switon\Core\Exception;

/**
 * Signals JSON encoding or decoding failures.
 *
 * @see \Switon\Core\Exception
 * @see \Switon\Core\Json::parse()
 */
class JsonException extends Exception
{
}
