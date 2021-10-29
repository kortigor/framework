<?php

declare(strict_types=1);

namespace core\base;

use Sys;
use core\interfaces\KernelInterface;
use core\base\Controller;
use core\base\ExceptionsHandler;
use core\helpers\ArrayHelper;
use core\helpers\Url;
use core\data\Settings;
use core\di\Container;
use core\di\Configurator;
use core\routing\Router;
use core\routing\Route;
use core\runner\handler\Handler;
use core\http\HttpFactory;
use core\http\AppendStream;
use core\web\ServerRequest;
use core\web\View;
use core\web\AssetReg;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Base web application.
 * 
 * @property-read \core\web\User $user System user object instance.
 * @property-read \core\helpers\Formatter $formatter Formatter object instance.
 */
abstract class Application
{
	/**
	 * @var string Application id
	 */
	public string $id;
	/**
	 * @var string Charset currently used for the application.
	 */
	public string $charset;
	/**
	 * @var string the language that is meant to be used for end users. It is recommended that you
	 * use [IETF language tags](http://en.wikipedia.org/wiki/IETF_language_tag). For example, `en` stands
	 * for English, while `en-US` stands for English (United States).
	 */
	public string $language;
	/**
	 * @var string Time zone used by this application.
	 */
	public string $timeZone;
	/**
	 * @var string Remote IP address
	 */
	public string $remoteIp;
	/**
	 * @var string Application home relative url, i.e. "/", "/admin/"
	 */
	public string $homeUrl;
	/**
	 * @var ServerRequest Incoming server request.
	 * 
	 * Note: ServerRequest is immutable,
	 * so actual object passed through middlewares may differ from the incoming request.
	 */
	protected ServerRequest $request;
	/**
	 * @var Handler Application middlewares handler/runner
	 */
	protected Handler $handler;
	/**
	 * @var Container
	 */
	protected Container $container;
	/**
	 * @var KernelInterface
	 */
	protected KernelInterface $kernel;

	/**
	 * @var string Runtime path cache
	 */
	private string $_runtimePath;

	/**
	 * Constructor.
	 * 
	 * @param array $config Application config data.
	 */
	public function __construct(protected array $config = [])
	{
		Sys::$app = $this;
		$this->container = new Container;

		$this->preInit();
		$this->init();

		// Create PSR-15 application request handler
		$this->handler = $this->container->get('handler');

		// Create and init application kernel (handler's PSR-15 middlewares)
		$this->getKernel($this->handler)->init();
	}

	/**
	 * Container components getter via application property.
	 * 
	 * @param string $name Component alias or class name
	 * 
	 * @return object|null
	 */
	public function __get(string $name): ?object
	{
		// Get components only, not classes, singletons etc...
		if (!array_key_exists($name, c('main.container.components'))) {
			return null;
		}

		if (!$this->container->has($name)) {
			return null;
		}

		return $this->container->get($name);
	}

	/**
	 * Get application kernel configured with necessary middlewares.
	 * 
	 * @param Handler $handler Application handler
	 * 
	 * @return KernelInterface
	 */
	abstract protected function getKernel(Handler $handler): KernelInterface;

	/**
	 * Run application.
	 * 
	 * @return void
	 */
	public function run(): void
	{
		// Run kernel's middlewares and get response
		$response = $this->handler->handle($this->request);

		// Append output buffers content (if present) to response's body
		if (ob_get_level() && ob_get_length()) {
			$factory = new HttpFactory;
			$body = new AppendStream;
			while (ob_get_level() && ob_get_length()) {
				$buffer = ob_get_clean();
				$stream = $factory->createStream($buffer);
				$body->addStream($stream);
				unset($buffer);
			}

			$body->addStream($response->getBody());
			$response = $response->withBody($body);
		}

		// Emit response
		/** @var \core\runner\emitter\EmitterInterface $emitter */
		$emitter = $this->container->get('emitter');
		$emitter->emit($response);
	}

	/**
	 * Application minimal initialization.
	 * 
	 * @return void
	 */
	protected function preInit(): void
	{
		ExceptionsHandler::register();

		// Load application config from files
		$this->settings()->add('main', $this->config);

		$this->id		= c('main.appId');
		$this->charset	= c('main.charset');
		$this->language	= c('main.language.default');
		$this->timeZone	= c('main.timeZone');
		$this->remoteIp	= c('main.remoteIp');
		$this->homeUrl	= c('main.homeUrl');
		$this->request	= ServerRequest::fromGlobals()->withAttribute('homeUrl', $this->homeUrl);

		date_default_timezone_set($this->timeZone);
		mb_internal_encoding($this->charset);
	}

	/**
	 * Application specific initialization.
	 * 
	 * @return void
	 */
	protected function init(): void
	{
		$this->initContainer(c('main.container', []));
		$this->getRouter()->setUri($this->request->getUri());

		$viewDefaults = c('main.view');
		$viewDefaults['rootPath'] = c('main.rootPath');

		View::$defaults		= $viewDefaults;
		Url::$home			= $this->homeUrl;
		Url::$generator		= $this->getRouter()->getGenerator();
		Url::$uri			= $this->request->getUri();
		AssetReg::$rootPath	= c('main.rootPath');
	}

	/**
	 * DI container initialization.
	 * 
	 * @param array $config Container cofig.
	 * 
	 * @return void
	 */
	protected function initContainer(array $config): void
	{
		$confugurator = new Configurator($this->container);
		$confugurator->setSingletons($config['components'] ?? []);
		$confugurator->setSingletons($config['singletons'] ?? []);
		$confugurator->setDefinitions($config['dependencies'] ?? []);
	}

	/**
	 * Set application locale
	 * 
	 * @param string $locale
	 * 
	 * @return void
	 */
	public function setLocale(string $locale): void
	{
		$this->language = $locale;
		$this->formatter->locale = $locale;
		$this->formatter->language = $locale;
	}

	/**
	 * Access to settings data via path in dot format.
	 * 
	 * Examples:
	 * ```
	 * $app->settings()->maps | $app->c('maps');
	 * $app->settings()->maps['photo_marker_extensions'] | $app->c('maps.photo_marker_extensions');
	 * $app->settings()->maps['photo_marker_extensions']['marker1'] | $app->c('maps.photo_marker_extensions.marker1');
	 * ```
	 * @param string $path Path in dot format to config variable
	 * @param mixed $default Default value if path is invalid.
	 *
	 * @return mixed Config data or Default value if not exists.
	 */
	public function c(string $path, $default = null): mixed
	{
		$keys = explode('.', $path);
		$source = array_shift($keys);
		$path = implode('.', $keys);
		$data = $this->settings()->$source ?? $default;

		return $path ? ArrayHelper::getValue($data, $path, $default) : $data;
	}

	/**
	 * Settings object instance.
	 * 
	 * @return Settings
	 */
	public function settings(): Settings
	{
		return Settings::getInstance();
	}

	/**
	 * Application directory to store runtime files.
	 * 
	 * @return string
	 */
	public function runtimePath(): string
	{
		if (!isset($this->_runtimePath)) {
			$path = DATA_ROOT_PHP .	DS . 'runtime' . DS . $this->id;
			$this->_runtimePath = normalizePath($path);
		}

		return $this->_runtimePath;
	}

	/**
	 * Get application kernel middleware by id.
	 * 
	 * @param string $id Component id, same as middleware's class short name (without namespace).
	 * 
	 * @return MiddlewareInterface
	 * @throws InvalidArgumentException If middleware with given id was not found.
	 */
	public function getKernelById(string $id): MiddlewareInterface
	{
		return $this->handler->getById($id);
	}

	/**
	 * Get application url router.
	 * 
	 * @return Router
	 */
	public function getRouter(): Router
	{
		return $this->container->get(Router::class);
	}

	/**
	 * Get current controller.
	 * 
	 * @return Controller
	 */
	public function getController(): Controller
	{
		/** @var \core\middleware\ControllerRunner $middleware */
		$middleware = $this->handler->getById('ControllerRunner');
		return $middleware->getController();
	}

	/**
	 * Get current route.
	 * 
	 * @return Route
	 */
	public function getRoute(): Route
	{
		/** @var \core\middleware\ControllerRunner $middleware */
		$middleware = $this->handler->getById('ControllerRunner');
		return $middleware->getRoute();
	}
}