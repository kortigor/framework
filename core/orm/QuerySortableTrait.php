<?php

declare(strict_types=1);

namespace core\orm;

use Illuminate\Database\Eloquent\Builder;

trait QuerySortableTrait
{
	/**
	 * Local scope for ActiveRecord models to filter a result according query parameters.
	 * 
	 * You can simple call this scope like:
	 * ```
	 * $sort = new ModelQuerySort($request);
	 * Model::sort($sort)->get();
	 * ```
	 * @param Builder $query
	 * @param QuerySortableleInterface $filters
	 * 
	 * @return void
	 * @see https://laravel.com/docs/8.x/eloquent#local-scopes
	 * @see \core\orm\QueryFilterSort abstract.
	 */
	public function scopeSort(Builder $query, QuerySortableleInterface $filters): void
	{
		$filters->apply($query);
	}
}