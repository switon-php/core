<?php

declare(strict_types=1);

namespace Switon\Core\Exception;

use Switon\Core\NotFoundInterface;

/**
 * Signals missing or unreadable files.
 *
 * @see \Switon\Core\Exception\RuntimeException
 * @see \Switon\Core\Filesystem::read()
 */
class FileNotFoundException extends RuntimeException implements NotFoundInterface
{
    /**
     * Get HTTP status code for this exception.
     *
     * @return int HTTP status code (404 for Not Found)
     */
    public function getStatusCode(): int
    {
        return 404;
    }
}
