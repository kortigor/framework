<?php

declare(strict_types=1);

namespace customer\scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use customer\entities\Status;

/**
 * Scope to query entities only with 'active' status.
 * @see \customer\entities\Status
 * @see https://laravel.com/docs/8.x/eloquent#query-scopes
 */
class StatusActiveScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('status', Status::STATUS_ACTIVE);
    }
}
