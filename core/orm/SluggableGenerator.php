<?php

declare(strict_types=1);

namespace core\orm;

use InvalidArgumentException;
use core\exception\InvalidConfigException;
use core\helpers\ArrayHelper;
use core\helpers\Inflector;
use core\validators\Assert;

/**
 * Generate sluggable value for ActiveRecord models.
 */
class SluggableGenerator
{
    /**
     * @var string Slug source model's attribute name.
     */
    private string $source;

    /**
     * @var string Sluggable model's attribute name.
     */
    private string $sluggable;

    /**
     * @var bool Sluggable attribute value remains unchanged if source attribute value was changed.
     */
    private bool $isImmutable;

    /**
     * @var int Maximum allowed length of slug. According to length of DB table field.
     */
    private int $maxLength;

    /**
     * Constructor.
     * 
     * @param ActiveRecord $model Sluggable model.
     * @param array $options
     */
    public function __construct(private ActiveRecord $model, array $options)
    {
        $this->source = ArrayHelper::remove($options, 'source');
        $this->sluggable = ArrayHelper::remove($options, 'sluggable', 'slug');
        $this->isImmutable = ArrayHelper::remove($options, 'immutable', false);
        $this->maxLength = ArrayHelper::remove($options, 'maxLength', 255);
    }

    /**
     * Get generated slug.
     * 
     * @return string Slug value.
     */
    public function getSlug(): string
    {
        if (!$this->isNewSlugNeeded()) {
            return $this->model->{$this->sluggable};
        }

        $this->assertSourceNotEmpty();
        $source = $this->model->{$this->source};
        $slug = $this->generateSlug($source);
        $slug = $this->makeUnique($slug);

        return $slug;
    }

    /**
     * Checks whether the new slug generation is needed.
     * 
     * @return bool True if new slug needed.
     */
    protected function isNewSlugNeeded(): bool
    {
        if (empty($this->model->{$this->sluggable})) {
            return true;
        }

        if ($this->isImmutable) {
            return false;
        }

        if ($this->model->isDirty($this->source)) {
            return true;
        }

        return false;
    }

    /**
     * Generate slug.
     *
     * @param string $source String to be converted to the slug value.
     * @return string The conversion result.
     */
    protected function generateSlug(string $source): string
    {
        $slug = Inflector::slug($source);
        // Strip slug to maximum allowed length,
        $slug = mb_substr($slug, 0, $this->maxLength);

        return $slug;
    }

    /**
     * This method is called by `getSlug()` to generate the unique slug.
     * Works until generated slug is unique and returns it.
     * 
     * @param string $slug Basic slug value.
     * @return string Unique slug.
     * @see generateUniqueSlug()
     */
    protected function makeUnique(string $slug): string
    {
        $uniqueSlug = $slug;
        $iteration = 1;
        while (!$this->validateSlug($uniqueSlug)) {
            // Reserve symbols for current unique suffix:
            // '-' symbol + iteration number length.
            $iterationSuffixLength = strlen(strval($iteration)) + 1;
            $iterationSlug = mb_substr($slug, 0, $this->maxLength - $iterationSuffixLength);
            $uniqueSlug = $this->generateUniqueSlug($iterationSlug, $iteration);
            $iteration++;
        }

        return $uniqueSlug;
    }

    /**
     * Checks if given slug value is unique.
     * 
     * @param string $slug Slug value.
     * @return bool True whether slug is unique.
     */
    protected function validateSlug(string $slug): bool
    {
        try {
            Assert::uniqueAttribute($slug, $this->sluggable, $this->model);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    /**
     * Generates slug using increment of iteration.
     * 
     * @param string $baseSlug Base slug value.
     * @param int $iteration Iteration number.
     * @return string New slug value.
     */
    protected function generateUniqueSlug(string $baseSlug, int $iteration): string
    {
        return $baseSlug . '-' . strval($iteration);
    }

    /**
     * Assert slug source not empty.
     * 
     * @return void
     * @throws InvalidConfigException If source is empty.
     */
    protected function assertSourceNotEmpty(): void
    {
        $source = $this->model->{$this->source};
        if (empty($source)) {
            throw new InvalidConfigException('Empty slug source');
        }
    }
}