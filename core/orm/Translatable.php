<?php

declare(strict_types=1);

namespace core\orm;

class Translatable
{
    /**
     * Whether model is multilanguage (translatable).
     * 
     * @return bool
     */
    public static function is(ActiveRecord $model): bool
    {
        $traits = array_values(class_uses($model));
        $target = [TranslatableSplitTrait::class, TranslatableTrait::class];
        return !empty(array_intersect($target, $traits));
    }
}