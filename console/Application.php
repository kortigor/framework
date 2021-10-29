<?php

declare(strict_types=1);

namespace console;

use Sys;
use core\interfaces\KernelInterface;
use core\runner\handler\Handler;
use core\di\Container;
use core\di\Configurator;

/**
 * Base console application.
 */
abstract class Application extends \core\base\Application
{
	/**
	 * @inheritDoc
	 */
	public function __construct(protected array $config = [])
	{
		Sys::$app = $this;
		$this->container = new Container;

		$this->preInit();
		$this->init();
	}

	protected function preInit(): void
	{
		// Load application config from files
		$this->settings()->add('main', $this->config);

		$this->id		= c('main.appId');
		$this->charset	= c('main.charset');
		$this->language	= c('main.language.default');
		$this->timeZone	= c('main.timeZone');
		$this->remoteIp	= c('main.remoteIp');
		$this->homeUrl	= '';

		date_default_timezone_set($this->timeZone);
		mb_internal_encoding($this->charset);
	}

	/**
	 * @inheritDoc
	 */
	protected function init(): void
	{
		$this->initContainer(c('main.container', []));
	}

	/**
	 * @inheritDoc
	 */
	protected function initContainer(array $config): void
	{
		$confugurator = new Configurator($this->container);
		$confugurator->setSingletons($config['components'] ?? []);
		$confugurator->setSingletons($config['singletons'] ?? []);
		$confugurator->setDefinitions($config['dependencies'] ?? []);
	}

	/**
	 * @inheritDoc
	 */
	public function run(): void
	{
	}

	/**
	 * {@inheritDoc}
	 * 
	 * Just empty plug
	 */
	protected function getKernel(Handler $handler): KernelInterface
	{
		return new Kernel($handler, $this, $this->container);
	}
}