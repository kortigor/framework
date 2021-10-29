<?php

declare(strict_types=1);

namespace core\orm;

use Sys;

/**
 * Trait to work with multilang models.
 * 
 * Need to setup 2 models:
 * - Parent;
 * - Translations.
 * 
 * Parent model must implement abstract method `translatable()` (see `TranslatableConfigTrait`) returns array or translatable attributes names.
 * 
 * Translation model must have:
 *  - implementation of `\core\orm\TranslationSplitInterface`;
 *  - table field/attribute `language`;
 *  - fields/attributes with same names as described in `translatable()` method of parent model.
 * 
 * Translation model can have:
 *  - Class name in the same namespace like `ParentTranslation` (if parent class name is `Parent`) to auto resolve translation model name in parent model.
 *  - Any class name. In this case parent model must have specified $translationModel property with translation model class name,
 *    see `getTranslationModelName()`;
 * 
 * Examples;
 * ```
 * $faq = Faq::first();
 * echo $faq->t('ru')->title; // show title in russian language
 * echo $faq->t('en')->title; // show title in english language
 * echo $faq->title_ru; // show title in russian language (added '_ru' suffix)
 * echo $faq->title_en; // show title in english language (added '_en' suffix)
 * echo $faq->title; // show title in current application language
 * 
 * // Save/Update:
 * $faq->title = 'New Title';
 * $faq->push();
 * // OR
 * $faq->t('ru')->title = 'Новый вопрос';
 * $faq->t('en')->title = 'New Title';
 * $faq->push();
 * // OR
 * $faq->title_ru = 'Новый вопрос';
 * $faq->title_en = 'New Title';
 * $faq->push();
 * 
 * // Create:
 * $faq = new Faq;
 * $faq->name = 'Ivan'; // parent model
 * 
 * $tRU = $faq->translate()->make([
 *  'language' => 'ru',
 *  'title' => 'Вопрос',
 *  'question' => 'Зачем это?',
 *  'reply' => 'Потому что надо'
 * ]);
 * 
 * $tEN = $faq->translate()->make([
 *  'language' => 'en',
 *  'title' => 'Question',
 *  'question' => 'What for?',
 *  'reply' => 'Because of it'
 * ]);
 * 
 * $faq->addTranslation($tRU);
 * $faq->addTranslation($tEN);
 * $faq->push();
 * // OR
 * $faq->translate()->saveMany([$tRU, $tEN]);
 * ```
 * @see https://laravel.com/docs/8.x/eloquent-relationships#inserting-and-updating-related-models
 * @see \core\orm\TranslationSplitInterface
 * 
 * @property \Illuminate\Database\Eloquent\Collection $translate Collection of translation records. See `translate()` relation.
 * @property string|null $translationModel Translation model class name.
 */
trait TranslatableSplitTrait
{
    use TranslatableConfigTrait;

    private array $_cache_t = [];

    private string $_pattern_lang_suffix;

    /**
     * Get translation relation.
     * 
     * @return HasMany relation
     */
    public function translate()
    {
        $model = $this->getTranslationModelName();
        /** @var \core\orm\ActiveRecord $this */
        return $this->hasMany($model, 'parent_id');
    }

    /**
     * Get translation record.
     * 
     * @param string $language
     * 
     * @return ActiveRecord|null
     */
    public function t(string $language = ''): ?ActiveRecord
    {
        $language = $language ?: Sys::$app->language;
        if (empty($this->_cache_t)) {
            $collection = $this->translate;
            foreach ($this->getLanguageSupported() as $lang) {
                $translation = $collection->firstWhere('language', '=', $lang);
                $this->_cache_t[$lang] = $translation;
            }
        }

        return $this->_cache_t[$language] ?? null;
    }

    /**
     * Add translation model to translatable parent model
     * 
     * @param TranslationSplitInterface $model Translation model to add.
     * 
     * @return self
     */
    public function addTranslation(TranslationSplitInterface $model): self
    {
        /** @var \core\orm\ActiveRecord $this */
        /** @var \core\orm\ActiveRecord $model */
        $this->addToHasMany($model, 'translate', 'translated');
        return $this;
    }

    /**
     * {@inheritDoc}
     * 
     * Note: All translatable attributes is fillable.
     */
    public function fill(array $attributes)
    {
        parent::fill($attributes);

        if ($attributes) {
            foreach ($this->translatable() as $tname) {
                foreach ($this->getLanguageSupported() as $lang) {
                    $attribute = "{$tname}_{$lang}";
                    if (isset($attributes[$attribute])) {
                        $this->$attribute = $attributes[$attribute];
                    }
                }
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        $data = parent::toArray();

        // Model can have no translations if loaded by custom select without all attributes
        // Check translations availability before iterations
        if ($this->translate->isNotEmpty()) {
            foreach ($this->translatable() as $tname) {
                foreach ($this->getLanguageSupported() as $lang) {
                    $attribute = "{$tname}_{$lang}";
                    $data[$attribute] = $this->$attribute;
                }
            }
        }

        return $data;
    }

    /**
     * Translated model attribute magic getter.
     * 
     * @param string $key
     * 
     * @return mixed
     */
    public function __get($key)
    {
        // Access to real translatable attribute name.
        // Returns value in current application language.
        // If `getDefaultLangOnEmpty()` returns true and value in requested (non default) language is empty,
        // then value in default language will be returned.
        if ($this->isTranslatableAttribute($key)) {
            $lang = Sys::$app->language;
            $value = $this->t($lang)->$key;
            if (empty($value) && $this->getDefaultLangOnEmpty() && $lang !== $this->getDefaultLanguage()) {
                $value = $this->t($this->getDefaultLanguage())->$key;
            }
            return $value;
        }

        // Access to translatable attribute with language suffix.
        // Like 'title_ru', 'title_en', wherein real name is 'title'.
        // Returns value with requested language.
        $matches = [];
        if (preg_match($this->getPatternLangSuffix(), $key, $matches)) {
            $keyReal = $matches[1];
            $lang = $matches[2];
            $value = $this->t($lang)->$keyReal;
            return $value;
        }

        return parent::__get($key);
    }

    /**
     * Translated model attribute magic setter.
     * 
     * @param string $key
     * @param mixed $value
     * 
     * @return mixed
     */
    public function __set($key, $value)
    {
        if ($this->isTranslatableAttribute($key)) {
            $this->t(Sys::$app->language)->$key = $value;
            return;
        }

        $matches = [];
        if (preg_match($this->getPatternLangSuffix(), $key, $matches)) {
            $keyReal = $matches[1];
            $lang = $matches[2];
            $this->t($lang)->$keyReal = $value;
            return;
        }

        parent::__set($key, $value);
    }

    /**
     * Get translations model class name.
     * 
     * There is possible to define `$translationModel` property in the model to override default behavior.
     * 
     * @return string Translation model class name.
     */
    private function getTranslationModelName(): string
    {
        return $this->translationModel ?? '\\' . get_class($this) . 'Translation';
    }

    /**
     * Get regexp pattern for translatable attribute with language suffix.
     * Pattern should be like: `/^(title|question|reply)_(ru|en)$/i`
     * 
     * @return string generated REGEXP pattern.
     */
    private function getPatternLangSuffix(): string
    {
        if (!isset($this->_pattern_lang_suffix)) {
            $translatable = implode('|', $this->translatable());
            $languages = implode('|', $this->getLanguageSupported());
            $this->_pattern_lang_suffix = '/^(' . $translatable . ')_(' . $languages . ')$/i';
        }

        return $this->_pattern_lang_suffix;
    }
}