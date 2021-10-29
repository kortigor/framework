<?php

declare(strict_types=1);

namespace customer\entities;

/**
 * Helper trait to implement CommentableItemInterface.
 * 
 * @see CommentableItemInterface
 */
trait CommentableItemTrait
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable', 'item_class', 'item_id');
    }

    public function isCommentsAllowed(): bool
    {
        return $this->allow_comments;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(): ?bool
    {
        $this->deleteAggregate();
        return true;
    }

    /**
     * Add 'deleting' event handler to model implements CommentableItemInterface
     * Delete all comments for CommentableItemInterface when item delete.
     * 
     * @return mixed
     */
    protected static function bootCommentableItemTrait()
    {
        static::deleting(fn (CommentableItemInterface $model) => $model->comments()->delete());
    }
}
