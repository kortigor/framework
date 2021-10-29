<?php

declare(strict_types=1);

namespace core\middleware;

use ReflectionMethod;
use ReflectionParameter;
use core\routing\Route;
use core\exception\HttpException;
use core\base\Controller;
use core\data\Hydrator;
use core\base\ControllerHydrator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

class ControllerRunner implements MiddlewareInterface
{
	/**
	 * @var string Default controller, used if not set in rule.
	 */
	public string $defaultController = 'Default';

	/**
	 * @var string Default action, used if not set in rule.
	 */
	public string $defaultAction = 'Index';

	/**
	 * @var string Controller class name pattern (including namespace).
	 */
	public string $patternController = '%s\%sController';

	/**
	 * @var string Action method name pattern.
	 */
	public string $patternAction = 'action%s';

	/**
	 * @var string Сurrent controller class name
	 */
	private string $controllerClass;

	/**
	 * @var string Сurrent controller's action method name
	 */
	private string $action;

	/**
	 * @var string Сurrent controller's action arguments
	 */
	private array $actionArguments;

	/**
	 * @var Controller Current controller.
	 */
	private Controller $controller;

	/**
	 * @var Route Current route.
	 */
	private Route $route;

	/**
	 * @var ServerRequestInterface
	 */
	private ServerRequestInterface $request;

	/**
	 * Constructor.
	 * 
	 * @param string $controllerNamespace Application controllers namespace.
	 * @param Hydrator $hydrator Objects hydrator instance.
	 */
	public function __construct(private string $controllerNamespace, private Hydrator $hydrator)
	{
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$route = $request->getAttribute('route');

		$this->route = $route;
		$this->request = $request;
		$this->controllerClass = $this->determineController($route);
		$this->action = $this->determineAction($route);
		$this->controller = ControllerHydrator::hydrate($this->hydrator, $this->controllerClass, [
			'request' => $request,
		]);

		$this->assertCallable();
		$this->actionArguments = $this->determineArguments($route);

		$data = call_user_func_array([$this->controller, $this->action], $this->actionArguments);
		$response = $this->controller->getResponse()->withBodyData($data);

		return $response;
	}

	/**
	 * Determine controller class from route
	 * 
	 * @param Route $route Route to determine controller class
	 * 
	 * @return string Controller class name
	 * @throws HttpException If controller class does not exists
	 */
	private function determineController(Route $route): string
	{
		$name = $route->getController();
		$class = $this->getControllerClass($name);
		if (!class_exists($class)) {
			throw new HttpException(404, "Controller '{$name}' not found");
		}

		return $class;
	}

	/**
	 * Determine action method name from route
	 * 
	 * @param Route $route
	 * 
	 * @return string
	 */
	private function determineAction(Route $route): string
	{
		$action = $route->getAction() ?: $this->defaultAction;
		return sprintf($this->patternAction, ucfirst($action));
	}

	/**
	 * Get action arguments.
	 * 
	 * PHP8 requires arguments list passed to method via array to be same as method's declared parameters.
	 * Therefore need to filter out route parameters which are not declared in action method.
	 * 
	 * @param Route $route
	 * 
	 * @return array Controller's action arguments. Associative array in pairs name => value.
	 */
	private function determineArguments(Route $route): array
	{
		$arguments = $route->getParameters();

		/** @var ReflectionParameter[] $parameters List of parameters declared in action method */
		$parameters = (new ReflectionMethod($this->controller, $this->action))->getParameters();

		/** @var string[] $parametersList List of parameter names declared in action method */
		$parametersList = array_map(
			fn (ReflectionParameter $parameter): string => $parameter->getName(),
			$parameters
		);

		// Filter available arguments according action declared parameter names
		$actionArguments = array_filter(
			$arguments,
			fn (string $name): bool => in_array($name, $parametersList),
			ARRAY_FILTER_USE_KEY
		);

		return $actionArguments;
	}

	/**
	 * Assert determined controller and action is callable.
	 * 
	 * @return void
	 * @throws HttpException If controller/action not callable.
	 */
	private function assertCallable(): void
	{
		if (!is_callable([$this->controller, $this->action])) {
			throw new HttpException(
				404,
				"Action '{$this->action}' cannot be called in the controller {$this->controllerClass}"
			);
		}
	}

	/**
	 * Get default route
	 * 
	 * @return Route
	 */
	public function getDefaultRoute(): Route
	{
		$route = $this->defaultController . '/' . $this->defaultAction;
		return new Route($route);
	}

	/**
	 * Get current route
	 * 
	 * @return Route|null
	 */
	public function getRoute(): ?Route
	{
		return $this->route;
	}

	/**
	 * Get current controller
	 * 
	 * @return Controller|null
	 */
	public function getController(): ?Controller
	{
		return $this->controller ?? null;
	}

	/**
	 * Get current controller action
	 * 
	 * @return string|null
	 */
	public function getAction(): ?string
	{
		return $this->action ?? null;
	}

	/**
	 * Get current controller action's arguments
	 * 
	 * @return array|null
	 */
	public function getActionArguments(): ?array
	{
		return $this->actionArguments ?? null;
	}

	/**
	 * Get controller class name, including namespace, according pattern
	 * 
	 * @param string $name
	 * 
	 * @return string
	 */
	public function getControllerClass(string $name): string
	{
		$name = $name ?: $this->defaultController;
		return sprintf($this->patternController, $this->controllerNamespace, ucfirst($name));
	}

	public function getDebugInfo(): string
	{
		$method = $this->request->getMethod();
		$path = $this->request->getUri()->getPath();

		return "<div class='small m-3'>
		<h6>Routing debug info:</h6>
		<p>Request method: <kbd>{$method}</kbd></p>
		<p>Path: <kbd>{$path}</kbd><p>
		<p>Controller class: <kbd>{$this->controllerClass}</kbd></p>
		<p>Action method: <kbd>{$this->action}</kbd></p>
		Request parameters:<pre>" . htmlspecialchars(print_r($_REQUEST, true), ENT_QUOTES) . "</pre>
		</div>";
	}
}