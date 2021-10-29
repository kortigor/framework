<?php

declare(strict_types=1);

namespace core\middleware;

use Throwable;
use DateTime;
use ReflectionClass;
use core\web\Response;
use core\web\ContentType;
use core\validators\Assert;
use core\helpers\FileHelper;
use core\exception\handler\ExceptionHandlerInterface;
use core\exception\handler\ThrowableHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Stream;

class ErrorHandler implements MiddlewareInterface
{
	/**
	 * @var string
	 */
	private string $format;

	/**
	 * Constructor.
	 * 
	 * @param Logger $logger Logger instance to write error logs.
	 */
	public function __construct(private Logger $logger)
	{
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {
			return $handler->handle($request);
		} catch (Throwable $e) {
			$this->log($request, $e);
			$this->format ??= $this->resolveFormatFromResponse($handler) ?? $this->resolveFormatFromRequest($request);
			/** @var Response $response */
			$response = $this->handleException($request, $e);
			return $response->withFormat($this->format);
		}
	}

	/**
	 * Handle exception
	 * 
	 * @param ServerRequestInterface $request
	 * @param Throwable $e
	 * @return ResponseInterface
	 */
	public function handleException(ServerRequestInterface $request, Throwable $e): ResponseInterface
	{
		/** @var \core\web\ServerRequest $request */
		$type = get_class($e);
		$handler = $this->getHandler($type);
		$content = $handler->handle($request, $e, $this->format);
		$response = Response::createNew()->withBodyData($content);
		return $response->withStatus($handler->getHttpStatus());
	}

	/**
	 * Get handler for given exception class
	 * 
	 * @param string $class
	 * 
	 * @return ExceptionHandlerInterface
	 */
	public function getHandler(string $class): ExceptionHandlerInterface
	{
		$options = [];
		$classShort = get_class_short($class);
		$class = "\\core\\exception\\handler\\{$classShort}Handler";
		if (class_exists($class)) {
			return new $class($options);
		}

		return new ThrowableHandler($options);
	}

	/**
	 * Set output format
	 * 
	 * @param string|null $format Error reporting format.
	 * If set, overrides any response object formatting settings.
	 * If not set, autodetect format depends of response format or request type (ajax or not)
	 * 
	 * @return void
	 * @throws InvalidArgumentException If format is invalid.
	 * 
	 * @see \core\web\ContentType
	 * @see \core\web\Response::setFormat()
	 * @see resolveFormatFromResponse()
	 * @see resolveFormatFromRequest()
	 */
	public function setFormat(string $format): void
	{
		$reflection = new ReflectionClass(ContentType::class);
		Assert::inArray($format, array_values($reflection->getConstants()));
		$this->format = $format;
	}

	/**
	 * Log handled error.
	 * 
	 * @param ServerRequestInterface $request
	 * @param Throwable $e
	 * 
	 * @return void
	 */
	private function log(ServerRequestInterface $request, Throwable $e): void
	{
		/** @var \core\web\ServerRequest $request */
		$date = (new DateTime)->format('Y-m-d');
		$file = FileHelper::getFilePath(c('main.logPath'), "uncatched_{$date}.error.log");
		$writer = new Stream($file);
		$message = sprintf(
			'[%s] %s %s thrown %s: %s, in %s on line %u',
			$request->getServerParam('REMOTE_ADDR'),
			$request->getMethod(),
			$request->getUri(),
			get_class($e),
			$e->getMessage(),
			$e->getFile(),
			$e->getLine()
		);
		$this->logger->addWriter($writer);
		$this->logger->err($message);
	}

	/**
	 * If error/exception was thrown in controller's area of responsibility,
	 * try to resolve response format from controller.
	 * 
	 * @param RequestHandlerInterface $handler
	 * 
	 * @return string|null response format or null if exception was thrown
	 * not in controller's area of responsibility.
	 */
	private function resolveFormatFromResponse(RequestHandlerInterface $handler): ?string
	{
		/**
		 * @var \core\runner\handler\Runner $handler
		 * @var \core\middleware\ControllerRunner $middleware
		 * */
		$middleware = $handler->getHandler()->getById('ControllerRunner');
		$controller = $middleware?->getController();
		return $controller ? $controller->getResponse()->getFormat() : null;
	}

	/**
	 * Determine response format based on request type
	 * 
	 * @param ServerRequestInterface $request
	 * 
	 * @return string
	 */
	private function resolveFormatFromRequest(ServerRequestInterface $request): string
	{
		/** @var \core\web\ServerRequest $request */
		return $request->isAjax() ? ContentType::FORMAT_JSON : ContentType::FORMAT_HTML;
	}
}