<?php

declare(strict_types=1);

namespace console;

use core\base\Kernel as BaseKernel;

/**
 * Console application kernel.
 */
final class Kernel extends BaseKernel
{
	/**
	 * @inheritDoc
	 */
	public function middleware(): iterable
	{
		return [];
	}
}