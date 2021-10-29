<?php

declare(strict_types=1);

namespace core\base;

use core\interfaces\KernelInterface;
use core\runner\handler\Handler;
use core\di\Container;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Base web application middlewares kernel.
 */
abstract class Kernel implements KernelInterface
{
	/**
	 * @var MiddlewareInterface|array|callable[] Array of middleware definitions added to begin of kernel stack.
	 * @see begin()
	 */
	protected array $begin = [];

	/**
	 * @var MiddlewareInterface|array|callable[] Array of middleware definitions added to the end of kernel stack.
	 * @see final()
	 */
	protected array $final = [];

	/**
	 * Constructor.
	 * 
	 * @param Handler $handler Application middleware handler.
	 * @param Application $app Running application.
	 * @param Container $container DI container instance.
	 */
	public function __construct(protected Handler $handler, protected Application $app, protected Container $container)
	{
	}

	/**
	 * @inheritDoc
	 */
	abstract public function middleware(): iterable;

	/**
	 * {@inheritDoc}
	 */
	public function add(MiddlewareInterface|array|callable $middleware): self
	{
		$this->final[] = $middleware;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function insert(MiddlewareInterface|array|callable $middleware): self
	{
		array_unshift($this->begin, $middleware);
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function init(): void
	{
		foreach ($this->getIterator() as $definition) {
			$middleware = $this->getMiddleware($definition);
			$path = $this->getPath($definition);

			if ($middleware instanceof MiddlewareInterface) {
				$this->handler->use($middleware, $path);
			} elseif (is_callable($middleware)) {
				$args = $middleware();
				$args = (array) $args;
				$this->handler->use(...$args);
			}
		}
	}

	/**
	 * Kernel stack iterator.
	 * 
	 * @return iterable
	 */
	protected function getIterator(): iterable
	{
		yield from $this->begin;
		yield from $this->middleware();
		yield from $this->final;
	}

	/**
	 * Get middleware from definition
	 * 
	 * @param mixed $definition
	 * 
	 * @return callable|MiddlewareInterface
	 */
	protected function getMiddleware(mixed $definition): callable|MiddlewareInterface
	{
		return is_array($definition) ? $definition[0] : $definition;
	}

	/**
	 * Get path from definition
	 * 
	 * @param mixed $definition
	 * 
	 * @return string|null
	 */
	protected function getPath(mixed $definition): ?string
	{
		return is_array($definition) ? $definition[1] : null;
	}
}