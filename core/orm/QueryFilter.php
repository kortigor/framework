<?php

declare(strict_types=1);

namespace core\orm;

use Illuminate\Database\Eloquent\Builder;
use core\helpers\Inflector;
use core\web\ServerRequest;

/**
 * Abstract class to build query based filters for ActiveRecord models.
 */
abstract class QueryFilter implements QueryFilterableInterface
{
	/**
	 * @var bool Enable request array values for queries like 'path?param[]=value1&param[]=value2'.
	 * If disabled and request parameter value is array, then filtering method will be skipped.
	 */
	public bool $enableArrayValues = false;

	/**
	 * @var Builder
	 */
	protected Builder $builder;

	/**
	 * @var string delimiter for arrayable request parameter values
	 */
	protected string $delimiter = '|';

	/**
	 * @var array disabled filters
	 */
	protected array $disabled = [];

	/**
	 * Constructor.
	 * 
	 * @param ServerRequest $request
	 */
	public function __construct(protected ServerRequest $request)
	{
	}

	/**
	 * Apply filter.
	 * 
	 * @param Builder $builder
	 */
	public function apply(Builder $builder): void
	{
		$this->builder = $builder;

		if (empty($this->filters()) && is_callable([$this, 'defaultFilter']) && method_exists($this, 'defaultFilter')) {
			call_user_func([$this, 'defaultFilter']);
		}

		foreach ($this->filters() as $field => $value) {
			if ($this->isDisabled($field)) {
				continue;
			}

			if (is_array($value) && !$this->enableArrayValues) {
				continue;
			}

			$method = Inflector::variablize($field);
			$value = array_filter([$value]);
			if ($this->shouldCall($method, $value)) {
				call_user_func_array([$this, $method], $value);
			}
		}
	}

	/**
	 * Disable filter.
	 * 
	 * @param string $filter Filter name
	 */
	public function disable(string $filter): self
	{
		$this->disabled[] = $filter;
		return $this;
	}

	/**
	 * Export filters as array.
	 * 
	 * @return array
	 */
	public function toArray(): array
	{
		return array_filter($this->filters(), fn ($filter) => !empty($filter));
	}

	/**
	 * Check filter exists in request.
	 * 
	 * @param string $filter Filter name
	 * 
	 * @return bool
	 */
	protected function has(string $filter): bool
	{
		return array_key_exists($filter, $this->filters());
	}

	/**
	 * @param string $param
	 * 
	 * @return array
	 */
	protected function paramToArray(string $param): array
	{
		return explode($this->delimiter, $param);
	}

	/**
	 * Get request query parameters.
	 * 
	 * @return array
	 */
	protected function filters(): array
	{
		return $this->request->getQueryParams();
	}

	/**
	 * Helper for "LIKE" filter.
	 *
	 * @param string $column
	 * @param string $value
	 *
	 * @return Builder
	 */
	protected function like(string $column, string $value): Builder
	{
		return $this->builder->where($column, 'LIKE', '%' . $value . '%');
	}

	/**
	 * Helper for "=" filter.
	 *
	 * @param string $column
	 * @param string $value
	 *
	 * @return Builder
	 */
	protected function equals(string $column, string $value): Builder
	{
		return $this->builder->where($column, $value);
	}

	/**
	 * Check filter disabled.
	 * 
	 * @param string $filter Filter name.
	 * 
	 * @return bool true if disabled
	 */
	protected function isDisabled(string $filter): bool
	{
		return in_array($filter, $this->disabled);
	}

	/**
	 * Make sure the method should be called.
	 *
	 * @param string $methodName
	 * @param array $value
	 *
	 * @return bool true if should be call.
	 */
	protected function shouldCall(string $methodName, array $value): bool
	{
		if (!method_exists($this, $methodName)) {
			return false;
		}

		$method = new \ReflectionMethod($this, $methodName);
		/** @var \ReflectionParameter $parameter */
		$parameter = $method->getParameters()[0] ?? null;

		return $value
			? $method->getNumberOfParameters() > 0
			: $parameter === null || $parameter->isDefaultValueAvailable();
	}
}