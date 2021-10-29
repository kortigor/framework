<?php

declare(strict_types=1);

namespace core\exception\handler;

use Throwable;
use Psr\Http\Message\ServerRequestInterface;

interface ExceptionHandlerInterface
{
	/**
	 * Handle exception.
	 * 
	 * @param ServerRequestInterface $request
	 * @param Throwable $e
	 * 
	 * @return mixed Formatted content to visualize error data.
	 */
	public function handle(ServerRequestInterface $request, Throwable $e, string $format);

	/**
	 * Get http status according specific error.
	 * 
	 * @return int
	 */
	public function getHttpStatus(): int;
}