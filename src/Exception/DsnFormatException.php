<?php

declare(strict_types=1);

namespace Switon\Core\Exception;

/**
 * Signals malformed DSN strings from client or adapter parsing.
 *
 * @see \Switon\Db\Client
 * @see \Switon\Db\Connection\Adapter\Mysql
 * @see \Switon\Db\Connection\Adapter\Mssql
 * @see \Switon\Db\Connection\Adapter\Pgsql
 */
class DsnFormatException extends RuntimeException
{
}
