<?php

declare(strict_types=1);

namespace core\di;

/**
 * Definition scopes.
 */
class Scope
{
	/**
	 * @var int Normal scope. Each time on get object from DI container, a new object instance will be created.
	 */
	const NORMAL = 10;

	/**
	 * @var int Singleton scope. Only one object instance will be created on get object from DI container.
	 */
	const SINGLETON = 20;
}