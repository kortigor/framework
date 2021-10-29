<?php

declare(strict_types=1);

namespace customer\entities\translations;

use core\orm\ActiveRecord;
use core\orm\TranslationSplitInterface;

/**
 * @property int $id Translation id.
 * @property string $language Translation language.
 * @property string $name Article category name.
 * @property string $parent_id Article parent id (uuid)
 * @property \Carbon\Carbon $created_at Record creation date.
 * @property \Carbon\Carbon $updated_at Record last update date.
 */
final class ArticleCategory extends ActiveRecord implements TranslationSplitInterface
{
    protected $table = 'articles_category_translations';

    protected $fillable = [
        'language',
        'name',
        'parent_id',
    ];

    public function rules(): array
    {
        return [
            [['language', 'parent_id'], 'required'],
            [['parent_id'], 'uuid'],
        ];
    }

    public function translated()
    {
        return $this->belongsTo(\customer\entities\ArticleCategory::class, 'parent_id');
    }
}
