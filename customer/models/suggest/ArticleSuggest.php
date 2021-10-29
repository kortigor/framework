<?php

declare(strict_types=1);

namespace customer\models\suggest;

use customer\entities\Article;
use Illuminate\Database\Eloquent\Builder;

class ArticleSuggest extends SuggestAbstract
{
    /**
     * @var string|callable[] Fields to search in. Can be in dot format, like 'translate.name'
     */
    protected array $fields = [];

    /**
     * @return Builder
     */
    public function prepare(): Builder
    {
        return Article::filter($this->filter);
    }
}
