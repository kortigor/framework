<?php

declare(strict_types=1);

namespace core\orm;

trait SluggableTrait
{
    /**
     * Get sluggable config.
     * 
     * @return array
     * @see \core\orm\SluggableGenerator for available config options.
     */
    abstract public function sluggable(): array;

    /**
     * Get generated slug.
     * 
     * @return string
     */
    protected function getSlug(): string
    {
        $generator = new SluggableGenerator($this, $this->sluggable());
        return $generator->getSlug();
    }

    protected static function bootSluggableTrait()
    {
        static::saving(function ($model) {
            $sluggable = $model->sluggable()['sluggable'];
            $model->$sluggable = $model->getSlug();
        });
    }
}