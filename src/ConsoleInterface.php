<?php

declare(strict_types=1);

namespace Switon\Core;

use Stringable;

/**
 * Console I/O contract for CLI applications.
 *
 * Use when commands need styled output, interactive input, and standard
 * exit-code handling without coupling to a specific console implementation.
 * This is a published cross-package protocol; the default implementation lives in `Switon\Cli\Console`.
 *
 * Guidance: Prefer these methods over direct STDIN/STDOUT access so color, formatting, and event hooks stay consistent.
 *
 * @see \Switon\Cli\Console
 */
interface ConsoleInterface
{
    /** Success exit code. */
    public const int SUCCESS = 0;

    /** General failure exit code. */
    public const int FAILURE = 1;

    /** Invalid input/arguments exit code. */
    public const int INVALID = 2;

    /** Detect whether ANSI colors are supported. */
    public function isSupportColor(): bool;

    /** Apply color/style options to text. */
    public function colorize(string $text, int $options = 0, int $width = 0): string;

    /** Print available color/style samples. */
    public function sampleColorizer(): void;

    /** Write one message without trailing newline. */
    /**
     * @param array<string, mixed> $context
     */
    public function write(string|Stringable $message, array $context = [], int $options = 0): void;

    /** Write one message with trailing newline. */
    /**
     * @param array<string, mixed> $context
     */
    public function writeLn(string|Stringable $message = '', array $context = [], int $options = 0): void;

    /** Write a debug message. */
    /**
     * @param array<string, mixed> $context
     */
    public function debug(string|Stringable $message = '', array $context = [], int $options = 0): void;

    /** Write an info message. */
    /**
     * @param array<string, mixed> $context
     */
    public function info(string|Stringable $message, array $context = []): void;

    /** Write a warning message. */
    /**
     * @param array<string, mixed> $context
     */
    public function warning(string|Stringable $message, array $context = []): void;

    /** Write a success message. */
    /**
     * @param array<string, mixed> $context
     */
    public function success(string|Stringable $message, array $context = []): void;

    /**
     * Write an error message and return exit code.
     *
     * @param array<string, mixed> $context
     * @param int $code Exit code to return
     */
    public function error(string|Stringable $message, array $context = [], int $code = 1): int;

    /**
     * Render progress output.
     *
     * @param mixed $value Optional progress value
     */
    public function progress(string|Stringable $message, mixed $value = null): void;

    /** Read one line from input. */
    public function read(): string;

    /** Ask a question and return user input. */
    public function ask(string $message): string;

    /** Ask for yes/no confirmation. */
    public function confirm(string $message, bool $default = true): bool;

    /**
     * Ask user to choose from options.
     *
     * @param array<string|int, string> $options
     */
    public function choice(string $message, array $options, string|int|null $default = null): string|int;

    /** Ask for hidden input (for example passwords/tokens). */
    public function secret(string $message): string;

    /**
     * Render a message block.
     *
     * @param string|array<string> $messages
     */
    public function block(string|array $messages, ?string $type = null, ?string $prefix = null, bool $padding = true): void;

    /** Render a section heading. */
    public function section(string $message): void;

    /** Render a note message. */
    public function note(string $message): void;

    /** Render a caution message. */
    public function caution(string $message): void;

    /**
     * Render a bulleted list.
     *
     * @param array<string> $items
     */
    public function listing(array $items): void;

    /**
     * Output a table with aligned columns (header + rows).
     *
     * When <code>$headers</code> is empty, only data rows are output (no header line).
     * Column count is then derived from the rows. Null cells are displayed as <code>-</code>.
     * When <code>$withRowNumber</code> is true, a leading column with header <code>#</code> and row numbers is added.
     *
     * @param list<string> $headers Column headers, or empty to skip header row
     * @param array<array<int|float|string|null>> $rows Each row is a list of cell values
     * @param int $minWidth Minimum width per column (default 8)
     * @param bool $withRowNumber Prepend row number column with header "#" (default true)
     */
    public function table(array $headers, array $rows, int $minWidth = 8, bool $withRowNumber = true): void;

    /** Output blank lines. */
    public function newLine(int $count = 1): void;

    /** Alias of writeLn(). */
    public function line(string $message = ''): void;
}
