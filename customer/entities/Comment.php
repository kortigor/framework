<?php

declare(strict_types=1);

namespace customer\entities;

use core\orm\ActiveRecord;

/**
 * @property int $id
 * @property string $text Comment text.
 * @property string $name Commentator name.
 * @property string $item_id Commented item ID (uuid).
 * @property string $item_class Commented item class.
 * @property CommentableItemInterface|ActiveRecord $commentable Commented item
 * @property Status $status Status
 * @property \Carbon\Carbon $created_at Record creation date.
 * @property \Carbon\Carbon $updated_at Record last update date.
 */
final class Comment extends ActiveRecord
{
    use AggregateTraitStatus;

    public bool $validateBeforeSave = true;

    protected $table = 'comments';

    protected $fillable = [
        'text',
        'name',
        'item_id',
        'item_class',
        'status',
    ];

    protected $casts = [
        'status' => casts\SerializeImmutable::class,
    ];

    public function rules(): array
    {
        return [
            ['item_id', 'uuid'],
            [['text', 'name'], 'required'],
            ['item_class', 'classExists'],
            ['item_class', 'implementsInterface' => CommentableItemInterface::class],
            ['status', 'oneOf' => [array_keys(Status::list())]],
        ];
    }

    public static function buildEmpty(): self
    {
        $new = new self();
        $new->status = Status::STATUS_ACTIVE;
        return $new;
    }

    public static function buildByUser(): self
    {
        $new = new self();
        $new->status = Status::STATUS_INACTIVE;
        return $new;
    }

    public function commentable()
    {
        /* https://stackoverflow.com/questions/61709348/how-to-set-up-morphto-parameters */
        return $this->morphTo('commentable', 'item_class', 'item_id', 'id');
    }
}
