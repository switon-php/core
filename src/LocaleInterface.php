<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Published request-local locale protocol for i18n and validation.
 *
 * @see \Switon\I18n\Locale
 * @see \Switon\I18n\ServiceProvider
 * @see \Switon\Core\TranslatorInterface
 */
interface LocaleInterface
{
    /**
     * Returns the effective locale for the current request context.
     */
    public function get(): string;

    /**
     * Sets locale for the current request context.
     *
     * Locale values are typically normalized to lowercase.
     *
     * @param string $locale Locale code (e.g. en, zh-cn)
     *
     * @return static Returns self for method chaining
     */
    public function set(string $locale): static;

    /**
     * Returns configured default locale when no request-local override exists.
     */
    public function getDefault(): string;
}
