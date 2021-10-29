<?php

declare(strict_types=1);

namespace core\di;

/**
 * DI container configurations loader.
 */
class Configurator
{
	/**
	 * Constructor
	 * 
	 * @param Container $container DI container to configure.
	 */
	public function __construct(private Container $container)
	{
	}

	/**
	 * Registers class definitions within this container.
	 *
	 * @param array $records array of definition records. There are two allowed formats of array.
	 * The first format (record with/without config only):
	 *  - key: class name, interface name or alias name. The key will be passed to the `set()` method
	 *    as a first argument `$class`.
	 *  - value: the definition associated with `$class`. Possible values are described in
	 *    `set()` documentation for the `$definition` parameter. Will be passed to the `set()` method
	 *    as the second argument `$definition`.
	 *
	 * Example:
	 * ```php
	 * $container->setDefinitions([
	 *     'web\Request' => 'app\components\Request',
	 *     'web\Response' => [
	 *         '@class' => 'app\components\Response',
	 *         'format' => 'json'
	 *     ],
	 *     'foo\Bar' => function () {
	 *         $qux = new Qux;
	 *         $foo = new Foo($qux);
	 *         return new Bar($foo);
	 *     }
	 * ]);
	 * ```
	 *
	 * The second format (record with parameters):
	 *  - key: class name, interface name or alias name. The key will be passed to the `set()` method
	 *    as a first argument `$class`.
	 *  - value: associative array of two elements: 
	 * 		"$d" ('definition') will be passed the `set()` method as the second argument `$definition`;
	 * 		"$p" ('params')as `$params`.
	 *  - or value: associative array of parameters passed into constructor.
	 *		Correct only in case of the key contains instantiable class name
	 *
	 * Example:
	 * ```php
	 * $container->setDefinitions([
	 *     'foo\Bar' => [
	 *          '@d' => ['@class' => 'app\Bar'],
	 *          '@p' => [Instance::of('baz')]
	 *      ],
	 *		\\ Class name and constructor parameters
	 * 		'app\components\Response' => [
	 *         '@p' => [
	 * 				'param1' => 1,
	 * 				'param2' => 2,
	 * 			],
	 *		\\ Class name and constructor parameters
	 * 		'response' => [
	 *         '@d' => ['@class' => \app\components\Response::class],
	 *         '@p' => [
	 * 				'param1' => 1,
	 * 				'param2' => 2,
	 * 			],
	 *     ],
	 *		\\ Class name and only constructor parameters without class definition
	 *		\\ Class defined in the key
	 *		\app\components\Response::class => [
	 * 			'param1' => 1,
	 * 			'param2' => 2,
	 *     ],
	 * ]);
	 * ```
	 *
	 * @see \core\di\Definition to know more about possible values of definitions
	 */
	public function setDefinitions(array $records): void
	{
		$this->setContainer($records, Scope::NORMAL);
	}

	/**
	 * Registers class definitions as singletons within this container by calling `set()->singleton()`.
	 *
	 * @param array $records array of singleton definitions. See `setDefinitions()`
	 * for allowed formats of array.
	 *
	 * @see setDefinitions() for allowed formats of $singletons parameter
	 * @see \core\di\Definition to know more about possible values of definitions
	 */
	public function setSingletons(array $records): void
	{
		$this->setContainer($records, Scope::SINGLETON);
	}

	/**
	 * Set container definitions.
	 * 
	 * @param array $records
	 * @param int $scope Definition scope
	 * 
	 * @return void
	 */
	protected function setContainer(array $records, int $scope): void
	{
		foreach ($records as $class => $record) {
			$def = $this->container->set($class, $this->parseDefinition($record), $this->parseParams($record));
			if ($scope === Scope::SINGLETON) {
				$def->singleton();
			}
		}
	}

	/**
	 * Parse class definition from definition record.
	 * 
	 * @param mixed $record
	 * 
	 * @return mixed
	 */
	protected function parseDefinition(mixed $record): mixed
	{
		// Only class definition (string, callable...).
		if (!is_array($record)) {
			return $record;
		}

		// Full advanced definition
		if (isset($record['@d'])) {
			return $record['@d'];
		}

		return $record;
	}

	/**
	 * Parse parameters to pass into constructor from definition record.
	 * 
	 * @param mixed $record
	 * 
	 * @return array
	 */
	protected function parseParams($record): array
	{
		// Only class definition (string, callable). Return no params.
		if (!is_array($record)) {
			return [];
		}

		// Full advanced definition
		if (isset($record['@p'])) {
			return $record['@p'];
		}

		// Only constructor parameters
		if (!isset($record['@class'])) {
			unset($record['@d']);
			return $record;
		}

		return [];
	}
}