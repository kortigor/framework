<?php

declare(strict_types=1);

namespace core\base;

use core\collections\FlashCollection;
use core\event\Manager as EventManager;
use core\web\View;
use core\web\Response;
use core\web\ServerRequest;
use core\web\SessionStorage;
use core\helpers\Url;

/**
 * Base abstract class to implement any controller.
 */
abstract class Controller
{
	/**
	 * @var ServerRequest
	 */
	protected ServerRequest $request;

	/**
	 * @var Response
	 */
	protected Response $response;

	/**
	 * @var View
	 */
	protected View $view;

	/**
	 * @var FlashCollection
	 */
	protected FlashCollection $flashes;

	/**
	 * Constructor.
	 * 
	 * Require controller's constructor to have an empty signature.
	 */
	abstract public function __construct();

	/**
	 * Get event manager.
	 * 
	 * @return EventManager
	 */
	public function getEventManager(): EventManager
	{
		return $this->request->getAttribute('eventManager');
	}

	/**
	 * Get view object. To be initialized in implementation.
	 * 
	 * @return View
	 */
	public function getView(): View
	{
		return $this->view;
	}

	/**
	 * Flashes handler.
	 * 
	 * @return FlashCollection
	 */
	public function flash(): FlashCollection
	{
		if (!isset($this->flash)) {
			$this->flash = new FlashCollection(new SessionStorage('__flashes'));
		}

		return $this->flash;
	}

	/**
	 * Inject server request.
	 * 
	 * @param ServerRequest $request
	 * 
	 * @return self
	 */
	public function setRequest(ServerRequest $request): self
	{
		$this->request = $request;
		return $this;
	}

	/**
	 * Inject response object to controller.
	 * 
	 * @param Response $response
	 * 
	 * @return self
	 */
	public function setResponse(Response $response): self
	{
		$this->response = $response;
		return $this;
	}

	/**
	 * Get request from controller
	 * 
	 * @return ServerRequest
	 */
	public function getRequest(): ServerRequest
	{
		return $this->request;
	}

	/**
	 * Get response from controller
	 * 
	 * @return Response
	 */
	public function getResponse(): Response
	{
		return $this->response;
	}

	/**
	 * Redirect browser.
	 * 
	 * @param string|array $args Url to redirect:
	 *  - string: url to redirect;
	 *  - array: options for create url by generator.
	 * @param bool $permanent permanent redirect or no, using http codes:
	 * - true for 301 permanent redirect, cached by browser;
	 * - false for 302 temporary redirect, not cached.
	 * 
	 * @return void
	 * @see Url::to()
	 */
	protected function redirect(string|array $options, bool $permanent = false): void
	{
		$url = is_array($options) ? Url::to($options) : $options;
		$status = $permanent ? 301 : 302;
		$this->response = $this->response->withRedirect($url, $status);
	}

	/**
	 * Refresh current page.
	 * 
	 * @return void
	 */
	protected function refresh(): void
	{
		$this->response = $this->response->withRedirect(Url::current());
	}

	/**
	 * Check option in url path
	 * 
	 * @param string $option
	 * 
	 * @return bool
	 * @see Url::hasPathOption()
	 */
	protected function hasPathOption(string $option): bool
	{
		return Url::hasPathOption($option);
	}

	/**
	 * Get ID from SEO url path, like "/module/ID-blah-blah/"
	 * 
	 * @param int $ind default 2
	 * 
	 * @return int|null
	 * @see Url::getSeoId()
	 */
	protected function getSeoId(int $ind = 2): ?int
	{
		return Url::getSeoId($ind);
	}
}