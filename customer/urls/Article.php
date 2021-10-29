<?php

declare(strict_types=1);

namespace customer\urls;

use core\interfaces\ContentUrlInterface;
use customer\entities\Article as Entity;

class Article implements ContentUrlInterface
{
    public function __construct(private Entity $entity)
    {
    }

    public function getUrlOptions(): array
    {
        return ['Article', 'slug' => $this->entity->slug];
    }
}
