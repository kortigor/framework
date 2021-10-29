<?php

declare(strict_types=1);

namespace core\orm;

use core\web\ServerRequest;

/**
 * {@inheritDoc}
 * 
 * This class implements configurable query parameters filter.
 * 
 * Example: There is possible to set handled request parameter(s) by passing `$parameters` into constructor
 * ```
 * class Search extends QueryFilterConfigurable
 * {
 *  public function __construct(ServerRequest $request, protected array $fields, array $parameters = ['search'])
 *  {
 *      parent::__construct($request);
 *      $this->parameters = $parameters;
 *  }
 *
 *  public function __call(string $name, $args)
 *  {
 *      $query = $args[0];
 *      $this->buildSearch($query);
 *  }
 *
 *  protected function buildSearch(string $query)
 *  {
 *      $this->builder->whereLike($this->fields, $query);
 *  }
 *}
 * ```
 */
abstract class QueryFilterConfigurable extends QueryFilter
{
	/**
	 * @var array List of model fields to use in filtering.
	 */
	public array $fields = [];

	/**
	 * @var array Request parameters names to handle.
	 */
	public array $parameters = [];

	/**
	 * Constructor.
	 * 
	 * @param ServerRequest $request
	 * @param array $parameters Request parameters names to handle.
	 * Example: If request like 'path?search=bananas', so need to pass `['search]`
	 * @param array $fields List of model fields to use in filtering.
	 */
	public function __construct(ServerRequest $request, array $fields = [], array $parameters = [])
	{
		parent::__construct($request);

		if ($fields) {
			$this->fields = $fields;
		}

		if ($parameters) {
			$this->parameters = $parameters;
		}
	}

	/**
	 * Build database query according request parameter value
	 * 
	 * @param string|array $value Request parameter value.
	 * @return void
	 */
	public abstract function build(string|array $value);

	/**
	 * Magic getter instead of concrete methods is responsible to handle request according `self::$parameters`
	 * 
	 * @param string $name
	 * @param mixed $arguments
	 * 
	 * @return void
	 * @see $parameters
	 */
	public function __call(string $name, $arguments)
	{
		$text = $arguments[0];
		$this->build($text);
	}

	/**
	 * {@inheritDoc}
	 * 
	 * Should call if method name present in the handled request parameters
	 * @see $parameters
	 */
	protected function shouldCall(string $methodName, array $value): bool
	{
		return in_array($methodName, $this->parameters) && isset($value[0]);
	}
}