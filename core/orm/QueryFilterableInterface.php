<?php

declare(strict_types=1);

namespace core\orm;

use Illuminate\Database\Eloquent\Builder;

/**
 * Interface of class to build query based filters for ActiveRecord models.
 */
interface QueryFilterableInterface
{
	/**
	 * Apply filter.
	 * 
	 * @param Builder $builder
	 */
	public function apply(Builder $builder): void;
}