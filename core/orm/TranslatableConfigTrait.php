<?php

declare(strict_types=1);

namespace core\orm;

trait TranslatableConfigTrait
{
    /**
     * Get model's tranlatable attributes.
     * 
     * @return string[] Array of model's translatable attributes.
     */
    abstract public function translatable(): array;

    /**
     * Whether model's attribute is translatable.
     * 
     * @param string $attribute Attribute name to check
     * 
     * @return bool
     */
    public function isTranslatableAttribute(string $attribute): bool
    {
        return in_array($attribute, $this->translatable());
    }

    /**
     * Model's supported languages.
     * 
     * You can define `$languageSupported` property in model to override default behavior.
     * 
     * @return array
     */
    public function getLanguageSupported(): array
    {
        return $this->languageSupported ?? c('main.language.supported', ['ru', 'en']);
    }

    /**
     * Model's default language to fallback.
     * 
     * You can define `$defaultLanguage` property in model to override default behavior.
     * 
     * @return string
     */
    public function getDefaultLanguage(): string
    {
        return $this->defaultLanguage ?? 'ru';
    }

    /**
     * Fallback to default language if translated value is empty.
     * 
     * You can define `$defaultLangOnEmpty` property in model to override default behavior.
     * 
     * @return bool
     */
    public function getDefaultLangOnEmpty(): bool
    {
        return $this->defaultLangOnEmpty ?? true;
    }
}