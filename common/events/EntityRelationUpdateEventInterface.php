<?php

namespace common\events;

use Illuminate\Database\Eloquent\Collection;

interface EntityRelationUpdateEventInterface extends EntityUpdateEventInterface
{
    public function getCollectionBefore(): Collection;
    public function getChanges(): string;
}