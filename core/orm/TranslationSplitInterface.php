<?php

declare(strict_types=1);

namespace core\orm;

/**
 * Translation model interface.
 * All ActiveRecord models, contains translated values of parent model, MUST implement this interface.
 * 
 * @see TranslatableSplitTrait
 */
interface TranslationSplitInterface
{
    /**
     * Relation to translated model.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Translated model relation
     */
    public function translated();
}