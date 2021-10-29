<?php

declare(strict_types=1);

namespace core\interfaces;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Base web application kernel interface.
 */
interface KernelInterface
{
	/**
	 * Get middlewares definitions.
	 * 
	 * @return iterable<MiddlewareInterface|array|callable> Iterable of middleware definitions to use in application kernel.
	 * 
	 * Available definition formats:
	 *  - \Psr\Http\Server\MiddlewareInterface
	 *  - [\Psr\Http\Server\MiddlewareInterface, string $path]
	 * 
	 * Callable MUST return one of available formats.
	 * 
	 * @see \core\runner\handler\Handler::use()
	 */
	public function middleware(): iterable;

	/**
	 * Add middleware to the end of kernel middlewares stack.
	 * 
	 * @param MiddlewareInterface|array|callable $middleware Middleware definition.
	 * 
	 * @return self
	 * @see middleware() For full info.
	 */
	public function add(MiddlewareInterface|array|callable $middleware): self;

	/**
	 * Insert middleware to the start of kernel middlewares stack.
	 * 
	 * @param MiddlewareInterface|array|callable $middleware Middleware definition.
	 * 
	 * @return self
	 * @see middleware() For full info.
	 */
	public function insert(MiddlewareInterface|array|callable $middleware): self;

	/**
	 * Initialize kernel middlewares.
	 * 
	 * @return void
	 */
	public function init(): void;
}