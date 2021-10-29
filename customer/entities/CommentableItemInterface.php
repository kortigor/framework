<?php

declare(strict_types=1);

namespace customer\entities;

use Illuminate\Database\Eloquent\Collection;

/**
 * Interface for items can be commented.
 * @property Collection $comments Item comments collection.
 */
interface CommentableItemInterface
{
    /**
     * Item comments relation
     * 
     * @return mixed
     */
    public function comments();

    /**
     * Indicates comments allowed for this item or not.
     * 
     * @return bool True if allowed
     */
    public function isCommentsAllowed(): bool;

    /**
     * Get commentable item name.
     * 
     * @return string
     */
    public function getName(): string;
}
