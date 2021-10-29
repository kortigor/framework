<?php

declare(strict_types=1);

namespace core\exception\handler;

use Throwable;
use SimpleXMLElement;
use core\web\View;
use core\web\ContentType;
use core\base\ErrorMessage;
use Psr\Http\Message\ServerRequestInterface;

abstract class ExceptionHandlerAbstract implements ExceptionHandlerInterface
{
	/**
	 * @var string Template to render exception
	 */
	public string $template = '/core/views/exception';

	/**
	 * @var string Layout to render exception
	 */
	public string $layout = '/core/views/layouts/no_menu';

	/**
	 * @var string Layout to render exception in Ajax request
	 */
	public string $layoutAjax = '/core/views/layouts/blank';

	/**
	 * @var string Template to render exception in Ajax request
	 */
	public string $templateAjax = '/core/views/blank';

	/**
	 * @var int
	 */
	protected int $httpStatus = 500;

	/**
	 * @var array
	 */
	protected array $options;

	/**
	 * @var string[]
	 */
	protected array $messages = [];

	/**
	 * @var array
	 */
	public static array $defaultOptions = [
		'details' => true
	];

	/**
	 * @var View
	 */
	private View $view;

	public function __construct($options = [])
	{
		$this->options = array_merge(static::$defaultOptions, $options);
	}

	/**
	 * {@inheritDoc}
	 */
	abstract function handle(ServerRequestInterface $request, Throwable $e, string $format);

	abstract protected function renderHtml(Throwable $e, bool $isAjax): string;

	/**
	 * {@inheritDoc}
	 */
	public function getHttpStatus(): int
	{
		return $this->httpStatus;
	}

	/**
	 * Get View object to render
	 * 
	 * @return View
	 */
	public function getView(): View
	{
		if (!isset($this->view)) {
			$this->view = new View($this->layout);
		}
		return $this->view;
	}

	/**
	 * Ghet option value
	 * 
	 * @param string $name option name
	 * 
	 * @return mixed
	 */
	public function getOption(string $name)
	{
		return $this->options[$name] ?? null;
	}

	/**
	 * Render exception message
	 * 
	 * @param Throwable $e
	 * @param string $format
	 * 
	 * @return mixed
	 */
	public function render(Throwable $e, string $format, bool $isAjax = false)
	{
		return match ($format) {
			ContentType::FORMAT_HTML => $this->renderHtml($e, $isAjax),
			ContentType::FORMAT_JSON => $this->renderJson($e),
			ContentType::FORMAT_XML => $this->renderXml($e),
			default => $e->getMessage()
		};
	}

	protected function renderHtmlAjax(Throwable $e): string
	{
		$this->getView()->setLayout($this->layoutAjax);
		$data = implode(', ', $this->messages);
		return $this->getView()->renderPart($this->templateAjax, compact('data'));
	}

	protected function renderXml(Throwable $e): SimpleXMLElement
	{
		return new SimpleXMLElement($e->getMessage());
	}

	protected function renderJson(Throwable $e): ErrorMessage
	{
		$error = new ErrorMessage(get_class($e));
		foreach ($this->messages as $message) {
			$error->addMessage($message);
		}

		if (isDebugEnv() && ($this->options['details'] ?? false)) {
			$error->addMessage('in File: ' . $e->getFile());
			$error->addMessage('at Line: ' . $e->getLine());
		}

		return $error;
	}
}