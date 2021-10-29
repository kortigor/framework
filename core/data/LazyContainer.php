<?php

declare(strict_types=1);

namespace core\data;

use core\interfaces\ContainerInterface;
use core\exception\NotFoundException;

/**
 * Callbacks lazy container implementation.
 */
class LazyContainer implements ContainerInterface
{
	/**
	 * @var array Loaders pool ['id' => callable1, 'id2' => callable2].
	 */
	private array $pool = [];

	/**
	 * @var array Cache of results of callbacks was executed with `proxy()` method.
	 * @see proxy()
	 */
	private array $cache = [];

	/**
	 * Constructor.
	 * 
	 * @param array $pool associative array `id => callable`
	 */
	public function __construct(array $pool = [])
	{
		foreach ($pool as $id => $callback) {
			$this->add($id, $callback);
		}
	}

	public function __get(string $prop): callable
	{
		return $this->get($prop);
	}

	/**
	 * Add callback to pool.
	 * 
	 * @param string $id Callback id
	 * @param callable $callback
	 * 
	 * @return self
	 */
	public function add(string $id, callable $callback): self
	{
		$this->pool[$id] = $callback;
		return $this;
	}

	/**
	 * Remove callback from pool.
	 * 
	 * @param string $id Callback id
	 * 
	 * @return self
	 */
	public function remove(string $id): self
	{
		unset($this->pool[$id]);
		return $this;
	}

	/**
	 * Clear pool
	 * 
	 * @return self
	 */
	public function clear(): self
	{
		$this->pool = [];
		$this->cache = [];
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * Get callback entry from container.
	 * 
	 * @param string $id Callback id.
	 * 
	 * @return callable
	 * @throws NotFoundException If callback not exist in the pool.
	 * @see add()
	 */
	public function get(string $id): callable
	{
		if (!$this->has($id)) {
			throw new NotFoundException("Callback '{$id}' not exists in the container");
		}

		return $this->pool[$id];
	}

	/**
	 * {@inheritDoc}
	 */
	public function has(string $id): bool
	{
		return isset($this->pool[$id]);
	}

	/**
	 * Get cached execution result of callback.
	 * 
	 * @param string $id Callback id.
	 * 
	 * @return mixed Execution result of callback, from method `get()`
	 * @see get();
	 */
	public function proxy(string $id): mixed
	{
		if (!isset($this->cache[$id])) {
			$callback = $this->get($id);
			$this->cache[$id] = $callback();
		}

		return $this->cache[$id];
	}

	/**
	 * Create callback proxy. Get execution result.
	 * For the next calls, the result is taken from the cache.
	 * 
	 * Example:
	 * ```
	 * $container = new LazyContainer();
	 * 
	 * // bigResourcesToCreateObject() will not executed
	 * $obj = $container->lazy('test', function () {
	 * 		return bigResourcesToCreateObject();
	 * });
	 * 
	 * ...
	 * // bigResourcesToCreateObject() Executed at first call. The result is cached and returned.
	 * $obj->test; // same as $obj->get('test');
	 * ...
	 * // bigResourcesToCreateObject() Not executed. The cached result is returned.
	 * $obj->test;
	 * 
	 * ```
	 * 
	 * @param string $id Callback id.
	 * @param callable $callback
	 * 
	 * @return mixed Execution result of the callback.
	 * @see add()
	 * @see proxy()
	 */
	public function lazy(string $id, callable $callback): mixed
	{
		if (!$this->has($id)) {
			$this->add($id, $callback);
		}

		return $this->proxy($id);
	}
}