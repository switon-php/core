<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Locale-aware message translation contract.
 *
 * Use when code needs translated text without binding to a concrete locale loader or storage model.
 *
 * @see \Switon\I18n\Translator
 * @see \Switon\Core\LocaleInterface
 */
interface TranslatorInterface
{
    /**
     * Translates one key to localized text.
     *
     * @param string $id Translation key (for example <code>user.not_found</code>)
     * @param array<string, mixed> $bind Placeholder values
     * @param bool $useICU Whether to use ICU MessageFormat
     */
    public function translate(string $id, array $bind = [], bool $useICU = false): string;

    /**
     * Returns whether a translation key resolves to localized text.
     *
     * @param string $id Translation key (for example <code>user.not_found</code>)
     */
    public function has(string $id): bool;
}
