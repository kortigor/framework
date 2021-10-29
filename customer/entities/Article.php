<?php

declare(strict_types=1);

namespace customer\entities;

use core\orm\ActiveRecord;
use core\orm\TranslatableSplitTrait;
use core\orm\SluggableTrait;
use core\entities\Id;

/**
 * @property string $id Article unique ID in uuid6 format.
 * @property string $slug SEO slug.
 * @property string $title Article title in current language.
 * @property string $announce Article announce in current language..
 * @property string $text Article text in current language..
 * @property Status $status Article status
 * @property bool $allow_comments Allow comments or not.
 * @property ArticleCategory $category Article category object
 * @property \Carbon\Carbon $created_at Record creation date.
 * @property \Carbon\Carbon $updated_at Record last update date.
 * @property \Illuminate\Database\Eloquent\Relations\HasMany $translate translation
 * 
 * @property string $title_ru Article title.
 * @property string $title_en Article title.
 * @property string $announce_ru Article announce.
 * @property string $announce_en Article announce.
 * @property string $text_ru Article text.
 * @property string $text_en Article text.
 */
final class Article extends ActiveRecord implements AggregateRootInterface, CommentableItemInterface
{
    use TranslatableSplitTrait;
    use SluggableTrait;
    use AggregateTrait;
    use AggregateTraitStatus;
    use CommentableItemTrait;

    public $incrementing = false;
    protected $table = 'articles';
    protected $keyType = 'string';

    protected $casts = [
        'status' => casts\SerializeImmutable::class,
        'allow_comments' => 'boolean'
    ];

    protected $fillable = [
        'slug',
        'status',
        'category_id',
        'allow_comments'
    ];

    protected $with = ['translate'];

    /**
     * @var string Translation model name
     */
    protected string $translationModel = translations\Article::class;

    public function rules(): array
    {
        return [
            [['slug', 'status'], 'required'],
            [['id', 'category_id'], 'uuid'],
            ['slug', 'uniqueAttribute' => ['slug', $this]],
            ['status', 'oneOf' => [array_keys(Status::list())]],
        ];
    }

    public function translatable(): array
    {
        return [
            'title',
            'announce',
            'text'
        ];
    }

    public function sluggable(): array
    {
        return [
            'sluggable' => 'slug',
            'source' => 'title',
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
                'title' => '',
                'announce' => '',
                'text' => ''
            ]);
            $new->addTranslation($translation);
        }

        return $new;
    }

    /**
     * Article category relation
     * 
     * @return mixed
     */
    public function category()
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->title;
    }
}
