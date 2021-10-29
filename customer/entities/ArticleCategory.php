<?php

declare(strict_types=1);

namespace customer\entities;

use core\orm\ActiveRecord;
use core\orm\TranslatableSplitTrait;
use core\orm\SluggableTrait;
use core\entities\Id;

/**
 * @property string $id Category unique ID in uuid6 format.
 * @property string $slug SEO slug.
 * @property Status $status Category status
 * @property \Illuminate\Database\Eloquent\Collection $article Collection of Article objects related to category.
 * @property \Carbon\Carbon $created_at Record creation date.
 * @property \Carbon\Carbon $updated_at Record last update date.
 * @property \Illuminate\Database\Eloquent\Relations\HasMany $translate translation
 * @property string $name Category name in current language.
 * @property string $name_ru Category name in russian.
 * @property string $name_en Category name in english.
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection $article Collection of Article objects related to category.
 */
final class ArticleCategory extends ActiveRecord implements AggregateRootInterface
{
    use TranslatableSplitTrait;
    use SluggableTrait;
    use AggregateTrait;
    use AggregateTraitStatus;

    public $incrementing = false;
    protected $table = 'articles_category';
    protected $keyType = 'string';

    protected $casts = [
        'status' => casts\SerializeImmutable::class,
    ];

    protected $fillable = [
        'slug',
        'status',
    ];

    protected $with = ['translate'];

    /**
     * @var string Translation model name
     */
    protected string $translationModel = translations\ArticleCategory::class;

    public function rules(): array
    {
        return [
            [['slug', 'status'], 'required'],
            ['id', 'uuid'],
            ['slug', 'uniqueAttribute' => ['slug', $this]],
            ['status', 'oneOf' => [array_keys(Status::list())]],
        ];
    }

    public function sluggable(): array
    {
        return [
            'sluggable' => 'slug',
            'source' => 'name',
        ];
    }

    public function translatable(): array
    {
        return [
            'name',
        ];
    }

    public static function buildEmpty(): self
    {
        $new = new self();
        $new->id = Id::next()->getId();
        $new->status = Status::STATUS_ACTIVE;
        $new->order = self::max('order') + 1;
        foreach ($new->getLanguageSupported() as $language) {
            $translation = $new->translate()->make([
                'language' => $language,
                'name' => '',
            ]);
            $new->addTranslation($translation);
        }

        return $new;
    }

    public function article()
    {
        return $this->hasMany(Article::class, 'category_id');
    }
}
