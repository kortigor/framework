<?php

declare(strict_types=1);

namespace core\web;

use LogicException;
use core\base\Kernel as BaseKernel;
use core\middleware\{
	ContentFormatter,
	ErrorHandler,
	AuthByCookie,
	NegotiatorLanguage,
	AuthRequire,
	Pjax,
	CartHandle,
	CompareItemsHandle,
	Csrf,
	EventHandler,
	Routing,
	ControllerRunner,
};

/**
 * Web application middlewares kernel config.
 */
final class Kernel extends BaseKernel
{
	/**
	 * First common middlewares.
	 * 
	 * @return array
	 */
	protected function begin(): array
	{
		return [
			$this->container->get(ContentFormatter::class),
			$this->container->get(ErrorHandler::class),
			$this->container->get(EventHandler::class),
		];
	}

	/**
	 * Final common middlewares.
	 * 
	 * @return array
	 */
	protected function final(): array
	{
		return [
			$this->container->get(Routing::class),
			$this->container->get(ControllerRunner::class, [c('main.controllerNamespace')]),
		];
	}

	/**
	 * Frontend application middlewares.
	 * 
	 * @return array
	 */
	protected function frontend(): array
	{
		return [
			$this->container->get(NegotiatorLanguage::class, [$this->app->language, c('main.cookieLifeTime')]),
			$this->container->get(Pjax::class),
			$this->container->get(Csrf::class, [true, $this->app->id]),
			$this->container->get(AuthByCookie::class, [c('main.auth.identityClass'), c('main.auth.identityCookieName')]),
			$this->container->get(CartHandle::class, ['cartItems']),
			$this->container->get(CompareItemsHandle::class, ['compareItems']),
		];
	}

	/**
	 * Backend application middlewares.
	 * 
	 * @return array
	 */
	protected function backend(): array
	{
		return [
			$this->container->get(Pjax::class),
			$this->container->get(Csrf::class, [true, $this->app->id]),
			$this->container->get(AuthByCookie::class, [c('main.auth.identityClass'), c('main.auth.identityCookieName')]),
			$this->container->get(AuthRequire::class, [c('main.auth.returnUrlCookieName')]),
		];
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @throws LogicException If application id is unknown.
	 */
	public function middleware(): iterable
	{
		yield from $this->begin();
		yield from match ($this->app->id) {
			'frontend' => $this->frontend(),
			'backend' => $this->backend(),
			default => throw new LogicException("Invalid application id: " . $this->app->id)
		};
		yield from $this->final();
	}
}