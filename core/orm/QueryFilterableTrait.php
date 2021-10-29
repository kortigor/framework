<?php

declare(strict_types=1);

namespace core\orm;

use Illuminate\Database\Eloquent\Builder;

trait QueryFilterableTrait
{
	/**
	 * Local scope for ActiveRecord models to filter a result according query parameters.
	 * 
	 * You can simple call this scope like:
	 * ```
	 * $filter = new ModelQueryFilter($request);
	 * Model::filter($filter)->get();
	 * ```
	 * @param Builder $query
	 * @param QueryFilterableInterface $filters
	 * 
	 * @return void
	 * @see https://laravel.com/docs/8.x/eloquent#local-scopes
	 * @see \core\orm\QueryFilter abstract.
	 */
	public function scopeFilter(Builder $query, QueryFilterableInterface $filters): void
	{
		$filters->apply($query);
	}
}