<?php

declare(strict_types=1);

namespace core\orm;

use Sys;

trait TranslatableTrait
{
    use TranslatableConfigTrait;

    /**
     * By default, translated attribute name is concatenation of attribute name and "_$language" ('ru', 'en') suffix.
     * 
     * You can define `langSuffix()` method in model to override default behavior,
     * and set needed custom suffix for specified languages.
     * 
     * Example:
     * ```
     * ['ru' => '__RUSSIAN', 'en' => '__ENGLISH']
     * ```
     * 
     * @return array
     */
    protected function langSuffix(string $lang): string
    {
        return ['ru' => '_ru', 'en' => '_en'][$lang];
    }

    /**
     * Translated model attribute getter.
     * 
     * @param string $key
     * 
     * @return mixed
     */
    public function __get($key)
    {
        $value = parent::__get($this->resolveTranslatableAttribute($key));
        if (empty($value) && $this->defaultLangOnEmpty() && Sys::$app->language !== $this->defaultLanguage()) {
            $value = parent::__get($this->resolveTranslatableAttribute($key, $this->defaultLanguage()));
        }

        return $value;
    }

    /**
     * Translated model attribute setter.
     * 
     * @param string $key
     * @param mixed $value
     * 
     * @return mixed
     */
    public function __set($key, $value)
    {
        parent::__set($this->resolveTranslatableAttribute($key), $value);
    }

    /**
     * Resolve translated attribute name.
     * 
     * @param string $key
     * 
     * @return string
     */
    protected function resolveTranslatableAttribute(string $key, $lang = null): string
    {
        $lang ??= Sys::$app->language;
        $suffix = '';
        if (in_array($key, $this->translatable())) {
            $suffix = $this->langSuffix($lang);
        }

        return $key . $suffix;
    }
}
