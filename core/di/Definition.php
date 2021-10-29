<?php

declare(strict_types=1);

namespace core\di;

use Closure;
use core\exception\ContainerException;

/**
 * Definition represents class definition for DI container.
 */
class Definition
{
	/**
	 * @var array Definition in array format service keys, i.e. '@class'.
	 */
	protected const ARRAY_FORMAT_SERVICE_KEYS = [
		'@class',
	];

	/**
	 * @var mixed Class definition
	 */
	private mixed $definition;

	/**
	 * @var int Definition scope
	 * @see \core\di\Scope
	 */
	private int $scope = Scope::NORMAL;

	/**
	 * Constructor.
	 * 
	 * @param string $class Class name, interface name or alias name.
	 * @param mixed $definition Definition associated with `$class`. It can be one of the following:
	 * - a PHP callable: The callable will be executed when `get()` is invoked. The signature of the callable
	 *   should be `function ($container, $params, $config)`, where `$params` stands for the list of constructor
	 *   parameters, `$config` the object configuration, and `$container` the container object. The return value
	 *   of the callable will be returned by `get()` as the object instance requested.
	 * - a configuration array: the array contains name-value pairs that will be used to initialize the property
	 *   values of the newly created object when `get()` is called. The `class` element stands for the
	 *   the class of the object to be created. If `class` is not specified, `$class` will be used as the class name.
	 * - a string: a class name, an interface name or an alias name.
	 */
	public function __construct(private string $class, mixed $definition)
	{
		if (empty($definition)) {
			if (!$this->isClassNamespace($class)) {
				throw new ContainerException(
					"With empty '\$definition' a '\$class' parameter MUST contain fully qualified namespaced class name."
				);
			}

			$this->definition = $class;
			return;
		}

		if (is_string($definition) || is_callable($definition, true) || is_object($definition)) {
			$this->definition = $definition;
			return;
		}

		if (is_array($definition)) {
			if (!isset($definition['@class'])) {
				if (!$this->isClassNamespace($class)) {
					throw new ContainerException("Array format definition requires a '@class' member.");
				}

				$definition['@class'] = $class;
			}

			$this->definition = $definition;
			return;
		}

		throw new ContainerException("Unsupported definition type for '{$class}': " . gettype($definition));
	}

	/**
	 * Whether class name is fully qualified namespaced.
	 * 
	 * @param string $class
	 * 
	 * @return bool
	 */
	private function isClassNamespace(string $class): bool
	{
		return strpos($class, '\\') !== false;
	}

	/**
	 * Get class definition.
	 * 
	 * @param bool $raw Whether to clear service information, i.e. '@class' etc...
	 * 
	 * @return mixed
	 * @see self::ARRAY_FORMAT_SERVICE_KEYS
	 */
	public function get(bool $raw = false): mixed
	{
		$definition = $this->definition;
		if (is_array($definition) && !$raw) {
			foreach (self::ARRAY_FORMAT_SERVICE_KEYS as $key) {
				unset($definition[$key]);
			}
		}

		return $definition;
	}

	/**
	 * Get definition concrete class name, NOT an alias name.
	 * 
	 * @return string
	 */
	public function getConcreteClass(): string
	{
		if (is_string($this->definition)) {
			return $this->definition;
		}

		if (is_string($this->definition['@class'] ?? null)) {
			return $this->definition['@class'];
		}

		return $this->class;
	}

	/**
	 * Set definition as singleton.
	 * 
	 * @return self
	 */
	public function singleton(): self
	{
		$this->scope = Scope::SINGLETON;
		return $this;
	}

	/**
	 * Whether definition is singleton.
	 * 
	 * @return bool
	 */
	public function isSingleton(): bool
	{
		return $this->scope === Scope::SINGLETON;
	}

	/**
	 * Whether definition is string format.
	 * 
	 * @return bool
	 */
	public function isString(): bool
	{
		return is_string($this->definition);
	}

	/**
	 * Whether definition is array format.
	 * 
	 * @return bool
	 */
	public function isArray(): bool
	{
		return is_array($this->definition);
	}

	/**
	 * Whether definition is callable format.
	 * 
	 * @return bool
	 */
	public function isCallable(): bool
	{
		return is_callable($this->definition);
	}

	/**
	 * Whether definition is object format.
	 * 
	 * @return bool
	 */
	public function isObject(): bool
	{
		return is_object($this->definition) && !$this->definition instanceof Closure;
	}
}