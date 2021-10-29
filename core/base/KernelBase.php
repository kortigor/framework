<?php

declare(strict_types=1);

namespace core\base;

use core\middleware\{
	ContentFormatter,
	ErrorHandler,
	Pjax,
	Csrf,
	EventHandler,
	Routing,
	ControllerRunner,
};

/**
 * Base web application middlewares kernel.
 */
class KernelBase extends Kernel
{
	/**
	 * @inheritDoc
	 */
	public function middleware(): iterable
	{
		return [
			$this->container->get(ContentFormatter::class),
			$this->container->get(ErrorHandler::class),
			$this->container->get(EventHandler::class),
			$this->container->get(Pjax::class),
			$this->container->get(Csrf::class, [$this->app->id]),
			$this->container->get(Routing::class),
			$this->container->get(ControllerRunner::class, [c('main.controllerNamespace')]),
		];
	}
}