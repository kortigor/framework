<?php

declare(strict_types=1);

namespace core\interfaces;

interface ViewHandlerInterface
{
	/**
	 * Handle view content.
	 * 
	 * Modify content as needed and return back.
	 * 
	 * @return string Content string after handling.
	 */
	public function handle(string $content): string;
}