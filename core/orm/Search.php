<?php

declare(strict_types=1);

namespace core\orm;

/**
 * Search ActiveRecord model attributes.
 */
class Search
{
    /**
     * Constructor.
     * 
     * @param ActiveRecord $model Model to search in.
     */
    public function __construct(protected ActiveRecord $model)
    {
    }

    /**
     * Find first attribute name, which value contains given string.
     * 
     * @param string $search String to search.
     * @param array<string|callable> $attributes Attributes to search for|in.
     *  - Helpful if need to search in mutated or relation attribute.
     *  - Note: When specified, the search is performed only in the specified attributes.
     * @param bool $asValue Return attribute value instead attribute name.
     * 
     * @return string|int|float|null Found attribute name or value, or null if was found nothing.
     */
    public function findFirst(string $search, array $attributes = [], bool $asValue = false): string|int|float|null
    {
        $search = mb_strtolower(trim($search));
        if (!$search) {
            return null;
        }

        // Search in all model's attributes. Return first result.
        if (!$attributes) {
            return $this->inAll($search, $asValue, false);
        }

        // Search in models's specified mutated attributes or by callable
        if ($res = $this->inSpecified($attributes, $search, $asValue, false)) {
            return $res;
        }

        // On translatable model try to find in the not default language(s)
        if ($res = $this->inTranslatable($attributes, $search, $asValue, false)) {
            return $res;
        }

        return null;
    }

    /**
     * Find all attributes names, which values contains given string.
     * 
     * @param string $search String to search.
     * @param array<string|callable> $attributes Attributes to search for|in.
     *  - Helpful if need to search in mutated or relation attribute.
     *  - Note: When specified, the search is performed only in the specified attributes.
     * @param bool $asValue Return attributes values instead attributes names.
     * 
     * @return array|null Found attributes names or values, or null if was found nothing.
     */
    public function findAll(string $search, array $attributes = [], bool $asValue = false): ?array
    {
        $search = mb_strtolower(trim($search));
        if (!$search) {
            return null;
        }

        // Search in all model's attributes. Return first result.
        if (!$attributes) {
            return $this->inAll($search, $asValue, true);
        }

        // Search in models's specified mutated attributes or by callable
        if ($res = $this->inSpecified($attributes, $search, $asValue, true)) {
            return $res;
        }

        // On translatable model try to find in the not default language(s)
        if ($res = $this->inTranslatable($attributes, $search, $asValue, true)) {
            return $res;
        }

        return null;
    }

    /**
     * Search in all models's attributes.
     * 
     * @param string $search
     * @param bool $asValue
     * @param bool $all Find all results if true, and first if false.
     * 
     * @return string|int|float|array|null Found attribute(s) name(s) or value(s), or null if was found nothing.
     */
    protected function inAll(string $search, bool $asValue, bool $all): string|int|float|array|null
    {
        $result = [];
        foreach ($this->model->getAttributes() as $attribute => $value) {
            if ($this->find($value, $search)) {
                $res = $asValue ? $value : $attribute;
                if (!$all) {
                    return $res;
                }
                $result[] = $res;
            }
        }

        return $result ?: null;
    }

    /**
     * Search in specified models's attributes including mutated or by callable.
     * 
     * Callable MUST have signature: 'function(ActiveRecord $model): string|array'
     * 
     * @param array $attributes
     * @param string $search
     * @param bool $asValue
     * @param bool $all Find all results if true, and first if false.
     * 
     * @return string|int|float|array|null Found attribute(s) name(s) or value(s), or null if was found nothing.
     */
    protected function inSpecified(array $attributes, string $search, bool $asValue, bool $all): string|int|float|array|null
    {
        $result = [];
        foreach ($attributes as $attribute) {
            $value = is_callable($attribute) ? $attribute($this->model) : $this->model->$attribute;
            // Array can be value of casted attributes or callable return value.
            // For convenience always convert value to array and iterate it.
            foreach ((array) $value as $val) {
                if ($this->find($val, $search)) {
                    $res = $asValue ? $val : (is_callable($attribute) ? '<callable>' : $attribute);
                    if (!$all) {
                        return $res;
                    }
                    $result[] = $res;
                }
            }
        }

        return $result ?: null;
    }

    /**
     * Search in translatable model not in default language(s).
     * 
     * @param array $attributes
     * @param string $search
     * @param bool $asValue
     * @param bool $all Find all results if true, and first if false.
     * 
     * @return string|int|float|array|null Found attribute(s) name(s) or value(s), or null if was found nothing.
     */
    protected function inTranslatable(array $attributes, string $search, bool $asValue, bool $all): string|int|float|array|null
    {
        if (!Translatable::is($this->model)) {
            return null;
        }

        $languages = array_filter(
            $this->model->getLanguageSupported(),
            fn ($lng) => $lng !== $this->model->getDefaultLanguage()
        );

        $result = [];
        foreach ($languages as $lang) {
            foreach ($attributes as $attribute) {
                // Skip callable and not translatable attribute
                if (is_callable($attribute) || !$this->model->isTranslatableAttribute($attribute)) {
                    continue;
                }

                $attribute = $attribute . '_' . $lang;
                $value = $this->model->$attribute;
                if ($this->find($value, $search)) {
                    $res = $asValue ? $value : $attribute;
                    if (!$all) {
                        return $res;
                    }
                    $result[] = $res;
                }
            }
        }

        return $result ?: null;
    }

    /**
     * Search string entry in attribute value
     * 
     * @param string $value
     * @param string $search
     * 
     * @return bool
     */
    protected function simpleSearch(string $value, string $search): bool
    {
        if (strstr(mb_strtolower($value), $search) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Find string in attribute value depends of stemmer usage.
     * 
     * @param string $value
     * @param string $search
     * 
     * @return bool
     */
    protected function find(string|int|float $value, string $search): bool
    {
        $value = (string) $value;
        return $this->simpleSearch($value, $search);
    }
}