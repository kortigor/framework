<?php

declare(strict_types=1);

namespace core\base;

use core\data\Hydrator;
use core\web\Response;

/**
 * Controller hydrator.
 */
class ControllerHydrator
{
	/**
	 * Create instance of controller.
	 * 
	 * Create instance and run controller's constructor.
	 * Inject properties values to controller.
	 * Using hydrator to avoid having to pass needed properties to constructor (it's uncomfortable),
	 * but have to able to use needed propertires in constructor.
	 * 
	 * Controller's constructor MUST require no arguments,
	 * i.e have following signature: `__construct()`
	 * 
	 * @param Hydrator $hydrator Hydrator instance.
	 * @param string $class Controller class.
	 * @param array $config Controller's properties values to set via Hydrator.
	 * 
	 * @return Controller Controller instance
	 */
	public static function hydrate(Hydrator $hydrator, string $class, array $config = []): Controller
	{
		$config = array_merge(static::configDefault(), $config);
		$controller = $hydrator->hydrate($class, $config);
		if (is_callable([$controller, '__construct'])) {
			$controller->__construct();
		}

		return $controller;
	}

	/**
	 * Default controller config.
	 * 
	 * @return array
	 */
	protected static function configDefault(): array
	{
		return [
			'response' => Response::createNew(),
		];
	}
}