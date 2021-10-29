<?php

declare(strict_types=1);

namespace core\di;

use ReflectionClass;
use ReflectionParameter;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionUnionType;
use ReflectionException;
use ReflectionFunctionAbstract;
use Closure;
use core\interfaces\ContainerInterface;
use core\exception\ContainerException;
use core\exception\NotFoundException;
use core\helpers\ArrayHelper;

/**
 * Container implements a dependency injection (http://en.wikipedia.org/wiki/Dependency_injection) container.
 *
 * A dependency injection (DI) container is an object that knows how to instantiate and configure objects and
 * all their dependent objects. For more information about DI, please refer to
 * Martin Fowler's article (http://martinfowler.com/articles/injection.html).
 *
 * Container supports constructor injection as well as property injection.
 *
 * To use Container, you first need to set up the class dependencies by calling `set()`.
 * You then call `get()` to create a new class object. Container will automatically instantiate
 * dependent objects, inject them into the object being created, configure and finally return the newly created object.
 *
 * Below is an example of using Container:
 *
 * ```php
 * interface UserFinderInterface
 * {
 *     function findUser();
 * }
 *
 * class UserFinder implements UserFinderInterface
 * {
 *     public $db;
 *
 *     public function __construct(Connection $db, $config = [])
 *     {
 *         $this->db = $db;
 *         parent::__construct($config);
 *     }
 *
 *     public function findUser()
 *     {
 *     }
 * }
 *
 * class UserLister
 * {
 *     public $finder;
 *
 *     public function __construct(UserFinderInterface $finder, $config = [])
 *     {
 *         $this->finder = $finder;
 *         parent::__construct($config);
 *     }
 * }
 *
 * $container = new Container;
 * $container->set('db\Connection', [
 *     'dsn' => '...',
 * ]);
 * $container->set('app\models\UserFinderInterface', [
 *     '@class' => 'app\models\UserFinder',
 * ]);
 * $container->set('userLister', 'app\models\UserLister');
 *
 * $lister = $container->get('userLister');
 *
 * // which is equivalent to:
 *
 * $db = new \db\Connection(['dsn' => '...']);
 * $finder = new UserFinder($db);
 * $lister = new UserLister($finder);
 * ```
 */
class Container implements ContainerInterface
{
	/**
	 * @var array Singleton objects indexed by their types
	 */
	private $singletons = [];
	/**
	 * @var array<string, Definition> Object definitions indexed by their types
	 */
	private $definitions = [];
	/**
	 * @var array<string, []> Constructor parameters indexed by object types
	 */
	private $params = [];
	/**
	 * @var array<string, ReflectionClass> Cached ReflectionClass objects indexed by class/interface names
	 */
	private $reflections = [];
	/**
	 * @var array<string, mixed> Cached dependencies indexed by class/interface names. Each class name
	 * is associated with a list of constructor parameter types or default values.
	 */
	private $dependencies = [];
	/**
	 * @var array<string, ReflectionClass> Cached ReflectionClass objects indexed by class/interface names
	 * Contains reflections from direct call `get()` without definition (no previously `set()` calling)
	 */
	private $noDefReflections = [];

	/**
	 * {@inheritDoc}
	 * 
	 * Returns an instance of the requested class.
	 *
	 * You may provide constructor parameters (`$params`) and object configurations (`$config`)
	 * that will be used during the creation of the instance.
	 *
	 * Note that if the class is declared to be singleton by calling `set()->singleton()`,
	 * the same instance of the class will be returned each time this method is called.
	 * In this case, the constructor parameters and object configurations will be used
	 * only if the class is instantiated the first time.
	 *
	 * @param string|Instance $class The class name or an alias name (e.g. `foo`)
	 * that was previously registered via `set()`.
	 * @param array $params a list of constructor parameter values. The parameters should be provided in the order
	 * they appear in the constructor declaration. If you want to skip some parameters, you should index the remaining
	 * ones with the integers that represent their positions in the constructor parameter list.
	 * @param array $config a list of name-value pairs that will be used to initialize the object properties.
	 * 
	 * @return object an instance of the requested class.
	 * 
	 * @throws NotFoundException If the class cannot be recognized or correspond to an invalid definition. From `build()`
	 * @throws ContainerException If resolved to an abstract class or an interface, of object invalid definition.
	 */
	public function get(string|Instance $class, array $params = [], array $config = []): object
	{
		if ($class instanceof Instance) {
			$class = $class->getId();
		}

		// Try to find singleton was instantiated earlier
		if (isset($this->singletons[$class])) {
			return $this->singletons[$class];
		}

		if (!$definition = $this->getDefinition($class)) {
			$params = $this->mergeParams($class, $params);
			return $this->build($class, $params, $config);
		}

		if ($definition->isObject()) {
			return $this->singletons[$class] = $definition->get();
		}

		if ($definition->isCallable()) {
			$object = $this->getFromCallableDefinition($definition, $class, $params, $config);
		} elseif ($definition->isArray() || $definition->isString()) {
			$object = $this->getFromArrayOrStringDefinition($definition, $class, $params, $config);
		} else {
			throw new ContainerException('Unexpected object definition type: ' . gettype($definition));
		}

		// Cache if object defined as singleton
		if ($definition->isSingleton()) {
			$this->singletons[$class] = $object;
		}

		return $object;
	}

	/**
	 * Registers a class definition with this container.
	 *
	 * For example,
	 *
	 * ```php
	 * // register a class name as is. This can be skipped.
	 * $container->set('db\Connection');
	 *
	 * // register an interface
	 * // When a class depends on the interface, the corresponding class
	 * // will be instantiated as the dependent object
	 * $container->set('mail\MailInterface', 'swiftmailer\Mailer');
	 *
	 * // register an alias name. You can use $container->get('foo')
	 * // to create an instance of Connection
	 * $container->set('foo', 'db\Connection');
	 *
	 * // register a class with configuration. The configuration
	 * // will be applied when the class is instantiated by get()
	 * $container->set('db\Connection', [
	 *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
	 *     'username' => 'root',
	 *     'password' => '',
	 *     'charset' => 'utf8',
	 * ]);
	 *
	 * // register an alias name with class configuration
	 * // In this case, a "class" element is required to specify the class
	 * $container->set('db', [
	 *     '@class' => 'db\Connection',
	 *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
	 *     'username' => 'root',
	 *     'password' => '',
	 *     'charset' => 'utf8',
	 * ]);
	 *
	 * // register a PHP callable
	 * // The callable will be executed when $container->get('db') is called
	 * $container->set('db', function ($container, $params, $config) {
	 *     return new \db\Connection($config);
	 * });
	 * ```
	 *
	 * If a class definition with the same name already exists, it will be overwritten with the new one.
	 * You may use `has()` to check if a class definition already exists.
	 *
	 * @param string $class Class name, interface name or alias name
	 * @param mixed $definition Definition associated with `$class`. It can be one of the following:
	 * - a PHP callable: The callable will be executed when `get()` is invoked. The signature of the callable
	 *   should be `function ($container, $params, $config)`, where `$params` stands for the list of constructor
	 *   parameters, `$config` the object configuration, and `$container` the container object. The return value
	 *   of the callable will be returned by `get()` as the object instance requested.
	 * - a configuration array: the array contains name-value pairs that will be used to initialize the property
	 *   values of the newly created object when `get()` is called. The `class` element stands for the
	 *   the class of the object to be created. If `class` is not specified, `$class` will be used as the class name.
	 * - a string: a class name, an interface name or an alias name.
	 * @param array $params List of constructor parameters. The parameters will be passed to the class
	 * constructor when `get()` is called.
	 * 
	 * @return Definition Normalized definition object
	 */
	public function set(string $class, $definition = [], array $params = []): Definition
	{
		$definitionObj = new Definition($class, $definition);
		$this->definitions[$class] = $definitionObj;
		$this->params[$class] = $params;
		unset($this->singletons[$class]);
		return $definitionObj;
	}

	/**
	 * Returns a value indicating whether the container has the definition of the specified name.
	 * 
	 * @param string $class class name, interface name or alias name
	 * @return bool whether the container has the definition of the specified name..
	 * @see set()
	 */
	public function has(string $class): bool
	{
		return isset($this->definitions[$class]);
	}

	/**
	 * Returns a value indicating whether the given name corresponds to a registered singleton.
	 * 
	 * @param string $class class name, interface name or alias name
	 * @param bool $checkInstance whether to check if the singleton has been instantiated.
	 * 
	 * @return bool whether the given name corresponds to a registered singleton. If `$checkInstance` is true,
	 * the method should return a value indicating whether the singleton has been instantiated.
	 */
	public function hasSingleton(string $class, bool $checkInstance = false): bool
	{
		return $checkInstance
			? isset($this->singletons[$class])
			: isset($this->definitions[$class]) && $this->definitions[$class]->isSingleton();
	}

	/**
	 * Removes the definition for the specified name.
	 * 
	 * @param string $class class name, interface name or alias name
	 */
	public function clear($class)
	{
		unset($this->definitions[$class], $this->singletons[$class]);
	}

	/**
	 * Returns the list of the object definitions or the loaded shared objects.
	 * 
	 * @return array List of the object definitions or the loaded shared objects (type or ID => definition or instance).
	 */
	public function getDefinitions(): array
	{
		return $this->definitions;
	}

	/**
	 * Returns definition object for requested class or an alias name.
	 * 
	 * @param string $class
	 * 
	 * @return Definition|null Definition or null if definition was not found.
	 */
	public function getDefinition(string $class): ?Definition
	{
		return $this->definitions[$class] ?? null;
	}

	/**
	 * Get object from callable definition.
	 * 
	 * @param Definition $definition
	 * @param mixed $class
	 * @param mixed $params
	 * @param mixed $config
	 * 
	 * @return object
	 */
	private function getFromCallableDefinition(Definition $definition, $class, $params, $config): object
	{
		$params = $this->resolveDependencies($this->mergeParams($class, $params));
		return call_user_func($definition->get(), $this, $params, $config);
	}

	/**
	 * Get object from array or string definition.
	 * 
	 * @param Definition $definition
	 * @param mixed $class
	 * @param mixed $params
	 * @param mixed $config
	 * 
	 * @return object
	 */
	private function getFromArrayOrStringDefinition(Definition $definition, $class, $params, $config): object
	{
		$concrete = $definition->getConcreteClass();
		if ($definition->isArray()) {
			$config = array_merge($definition->get(), $config);
		}

		$params = $this->mergeParams($class, $params);

		// Whether '$class' is an alias name, like 'foo', not class name like '\Foo'
		$isAlias = $class !== $concrete;
		$object = $isAlias
			? $this->get($concrete, $params, $config)
			: $this->build($class, $params, $config);

		return $object;
	}

	/**
	 * Creates an instance of the specified class.
	 * This method will resolve dependencies of the specified class, instantiate them, and inject
	 * them into the new instance of the specified class.
	 * 
	 * @param string $class The class name
	 * @param array $params Constructor parameters
	 * @param array $config Configurations to be applied to the new instance
	 * 
	 * @return object The newly created instance of the specified class
	 * 
	 * @throws ContainerException If resolved to an abstract class or an interface
	 * @throws NotFoundException If a dependency cannot be resolved. From `getDependencies()` method
	 */
	protected function build(string $class, array $params, array $config): object
	{
		/**
		 * @var ReflectionClass $reflection
		 * @var array $dependencies
		 **/
		list($reflection, $dependencies) = $this->getDependencies($class, $params);

		if (!$reflection->isInstantiable()) {
			throw new ContainerException($reflection->name);
		}

		foreach ($params as $name => $param) {
			$dependencies[$name] = $param;
		}

		$dependencies = $this->resolveDependencies($dependencies, $reflection);
		$object = $reflection->newInstanceArgs($dependencies);
		if (empty($config)) {
			return $object;
		}

		$config = $this->resolveDependencies($config);
		foreach ($config as $prop => $value) {
			$object->$prop = $value;
		}

		return $object;
	}

	/**
	 * Merges the user-specified constructor parameters with the ones registered via `set()`.
	 * 
	 * @param string $class class name, interface name or alias name
	 * @param array $params the constructor parameters
	 * 
	 * @return array the merged parameters
	 */
	protected function mergeParams(string $class, array $params): array
	{
		if (empty($this->params[$class])) {
			return $this->paramsToNamed($class, $params);
		}

		if (empty($params)) {
			return $this->paramsToNamed($class, $this->params[$class]);
		}

		$merged = $this->paramsToNamed($class, $this->params[$class]);
		$params = $this->paramsToNamed($class, $params);
		foreach ($params as $name => $value) {
			$merged[$name] = $value;
		}

		return $merged;
	}

	/**
	 * Convert unnamed parameters list (unnamed arguments) to named arguments for given class.
	 * 
	 * @param string $class
	 * @param array $params
	 * 
	 * @return array
	 * @throws ContainerException
	 */
	protected function paramsToNamed(string $class, array $params): array
	{
		// Try to collect not named parameters.
		$paramsUnnamed = [];
		foreach ($params as $name => $param) {
			if (is_int($name)) {
				$paramsUnnamed[$name] = $param;
				unset($params[$name]);
			}
		}

		if (!$paramsUnnamed) {
			return $params;
		}

		// If $params contains unnamed arguments, convert it to named
		$classN = $class;
		try {
			if ($this->has($classN)) {
				// If class definition exists, first ensure to get reflection of concrete class, not alias
				$classN = $this->getDefinition($classN)->getConcreteClass();
				list($reflection) = $this->getDependencies($classN, $params);

				// If reflection is an interface or abstract, both without constructor
				// try to get DI definition and get reflection of concrete class
				if (($reflection->isInterface() || $reflection->isAbstract()) && !$reflection->getConstructor()) {
					if (!$this->has($classN)) {
						throw new ContainerException(
							"Unable to get named parameters of interface or abstract without constructor and DI definition."
						);
					}

					$classN = $this->getDefinition($classN)->getConcreteClass();
					list($reflection) = $this->getDependencies($classN, $params);
				}
			} else {
				$reflection = $this->noDefReflections[$classN] ??= new ReflectionClass($classN);
			}
		} catch (ReflectionException | NotFoundException $e) {
			throw new ContainerException("Failed to resolve named parameters of class '{$class}'.", 0, $e);
		}

		if (!$constructor = $reflection->getConstructor()) {
			throw new ContainerException("Unable to get named parameters of class without constructor.");
		}

		foreach ($constructor->getParameters() as $param) {
			// Skip variadic parameter
			if ($param->isVariadic()) {
				continue;
			}

			$position = $param->getPosition();
			if (isset($paramsUnnamed[$position])) {
				$params[$param->getName()] = $paramsUnnamed[$position];
			}
		}

		return $params;
	}

	/**
	 * Returns the dependencies of the specified class.
	 * Dependencies is parameters to pass into class '__construct()' method.
	 * 
	 * @param string $class class name, interface name or alias name
	 * 
	 * @return array Dependencies of the specified class.
	 * 
	 * @throws NotFoundException if a dependency cannot be resolved.
	 */
	protected function getDependencies(string $class, array $params): array
	{
		if (isset($this->reflections[$class])) {
			return [$this->reflections[$class], $this->dependencies[$class]];
		}

		$dependencies = [];
		try {
			$reflection = $this->noDefReflections[$class] ?? new ReflectionClass($class);
		} catch (ReflectionException $e) {
			throw new NotFoundException("Failed to instantiate component or class '{$class}'.", 0, $e);
		}

		$constructor = $reflection->getConstructor();
		if ($constructor) {
			foreach ($constructor->getParameters() as $param) {
				if ($param->isVariadic()) {
					break;
				}

				$name = $param->getName();

				// If iterated parameter is passed into `build()` method via `$params` argument,
				// no need to get dependency, because passed parameter value will be used instead dependency anyway.
				if (isset($params[$name])) {
					continue;
				}

				if ($param->isDefaultValueAvailable()) {
					$dependencies[$name] = $param->getDefaultValue();
					continue;
				}

				if ($hintClass = $this->getHintClassName($param, $constructor, $class)) {
					$dependencies[$name] = Instance::of($hintClass);
				}
			}
		}

		$this->reflections[$class] = $reflection;
		$this->dependencies[$class] = $dependencies;

		return [$reflection, $dependencies];
	}

	/**
	 * Resolves dependencies by replacing them with the actual object instances.
	 * 
	 * @param array $dependencies The dependencies
	 * @param ReflectionClass $reflection The class reflection associated with the dependencies
	 * 
	 * @return array Resolved dependencies
	 * 
	 * @throws ContainerException If a dependency cannot be resolved.
	 */
	protected function resolveDependencies(array $dependencies, ReflectionClass $reflection = null): array
	{
		foreach ($dependencies as $index => $dependency) {
			if (!$dependency instanceof Instance) {
				continue;
			}

			try {
				/** @var Instance $dependency */
				$dependencies[$index] = $dependency->get($this);
			} catch (NotFoundException $e) {
				if ($reflection) {
					$name = $reflection->getConstructor()->getParameters()[$index]->getName();
					$class = $reflection->getName();
					throw new ContainerException("Unable to resolve dependency '{$name}' of class or component '{$class}'.", 0, $e);
				}
			}
		}

		return $dependencies;
	}

	/**
	 * Invoke a callback with resolving dependencies in parameters.
	 *
	 * This methods allows invoking a callback and let type hinted parameter names to be
	 * resolved as objects of the Container. It additionally allow calling function using named parameters.
	 *
	 * For example, the following callback may be invoked using the Container to resolve the formatter dependency:
	 *
	 * ```php
	 * $formatString = function($string, \i18n\Formatter $formatter) {
	 *    // ...
	 * }
	 * $container->invoke($formatString, ['string' => 'Hello World!']);
	 * ```
	 *
	 * This will pass the string `'Hello World!'` as the first param, and a formatter instance created
	 * by the DI container as the second param to the callable.
	 *
	 * @param callable $callback callable to be invoked.
	 * @param array $params The array of parameters for the function.
	 * This can be either a list of parameters, or an isAssociative array representing named function parameters.
	 * 
	 * @return mixed the callback return value.
	 * 
	 * @throws NotFoundException if a dependency cannot be resolved.
	 * @throws ContainerException If resolved to an abstract class or an interface, or if a dependency cannot be fulfilled
	 */
	public function invoke(callable $callback, array $params = []): mixed
	{
		return call_user_func_array($callback, $this->resolveCallableDependencies($callback, $params));
	}

	/**
	 * Resolve dependencies for a function.
	 *
	 * This method can be used to implement similar functionality as provided by `invoke()` in other
	 * components.
	 *
	 * @param callable $callback Callable to be invoked.
	 * @param array $params The array of parameters for the function, can be either numeric or isAssociative.
	 * 
	 * @return array The resolved dependencies.
	 * 
	 * @throws NotFoundException if a dependency cannot be resolved.
	 * @throws ContainerException If resolved to an abstract class or an interface, or if a dependency cannot be fulfilled
	 */
	public function resolveCallableDependencies(callable $callback, array $params = []): array
	{
		if (is_array($callback)) {
			$reflection = new ReflectionMethod($callback[0], $callback[1]);
		} elseif (is_object($callback) && !$callback instanceof Closure) {
			$reflection = new ReflectionMethod($callback, '__invoke');
		} else {
			$reflection = new ReflectionFunction($callback);
		}

		$args = [];
		$isAssociative = ArrayHelper::isAssociative($params);

		foreach ($reflection->getParameters() as $param) {
			$name = $param->getName();
			$hintClass = $this->getHintClassName($param, $reflection);
			if ($hintClass) {
				if ($param->isVariadic()) {
					$args = array_merge($args, array_values($params));
					break;
				}

				if ($isAssociative && isset($params[$name]) && $params[$name] instanceof $hintClass) {
					$args[$name] = $params[$name];
					unset($params[$name]);
					continue;
				}

				if (!$isAssociative && isset($params[0]) && $params[0] instanceof $hintClass) {
					$args[$name] = array_shift($params);
					continue;
				}

				// If the argument is optional catch not found exceptions
				try {
					$args[$name] = $this->get($hintClass);
				} catch (NotFoundException $e) {
					if (!$param->isDefaultValueAvailable()) {
						throw $e;
					}

					$args[$name] = $param->getDefaultValue();
				}
				continue;
			}

			if ($isAssociative && isset($params[$name])) {
				$args[$name] = $params[$name];
				unset($params[$name]);
				continue;
			}

			if (!$isAssociative && count($params)) {
				$args[$name] = array_shift($params);
				continue;
			}

			if ($param->isDefaultValueAvailable()) {
				$args[$name] = $param->getDefaultValue();
				continue;
			}

			if (!$param->isOptional()) {
				$funcName = $reflection->getName();
				throw new ContainerException("Missing required parameter '{$name}' when calling '{$funcName}'.");
			}
		}

		foreach ($params as $name => $value) {
			$args[$name] = $value;
		}

		return $args;
	}

	/**
	 * Get parameter type hint class name if specified.
	 * 
	 * @param ReflectionParameter $parameter
	 * @param ReflectionFunctionAbstract $method Parameter's method or function abstract
	 * @param string $class Parameter method's class if present (no function or Closure)
	 * 
	 * @return string|null Typed parameter class:
	 *  - null If parameter have no type hinting or type is not a class
	 *  - string Class type name
	 * 
	 * @throws ContainerException If parameter have union type hinting, see https://github.com/PHP-DI/PHP-DI/issues/767
	 */
	protected function getHintClassName(ReflectionParameter $parameter, ReflectionFunctionAbstract $method, string $class = null): ?string
	{
		$type = $parameter->getType();
		if ($type === null) {
			return null;
		}

		if ($type instanceof ReflectionUnionType) {
			if ($class) {
				$message = sprintf("method '%s::%s()'", $class, $method->getName());
			} else {
				$message = sprintf("function '%s'", $method->getName());
			}

			throw new ContainerException(
				sprintf(
					"Unable to get class type hint for parameter '%s' with union type '%s', in %s",
					$parameter->getName(),
					implode('|', $type->getTypes()),
					$message
				)
			);
		}

		if ($type->isBuiltin()) {
			return null;
		}

		return $type->getName();
	}
}