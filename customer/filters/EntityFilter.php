<?php

declare(strict_types=1);

namespace customer\filters;

use Illuminate\Database\Eloquent\Builder;
use core\orm\QueryFilter;
use customer\entities\Status;

class EntityFilter extends QueryFilter
{
    /**
     * @param string $status
     */
    public function status(string $status)
    {
        $condition = match ($status) {
            'active' => ['status' => Status::STATUS_ACTIVE],
            'blocked' => ['status' => Status::STATUS_INACTIVE],
            default => []
        };

        $this->builder->where($condition);
    }

    /**
     * @param string $slug
     * 
     * @return mixed
     */
    public function category(string $slug)
    {
        $this->builder->whereHas('category', fn (Builder $query) => $query->where('slug', $slug));
    }

    /**
     * @param string $title
     */
    public function title(string $title)
    {
        $words = array_filter(explode(' ', $title));

        $this->builder->whereHas('translate', function (Builder $query) use ($words) {
            foreach ($words as $word) {
                $query->where('title', 'like', "%$word%");
            }
        });
    }
}