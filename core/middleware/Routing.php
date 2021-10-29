<?php

declare(strict_types=1);

namespace core\middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use core\exception\HttpException;
use core\routing\Route;
use core\routing\Router;
use core\routing\InvalidRoutingException;

class Routing implements MiddlewareInterface
{
	/**
	 * Constructor.
	 * 
	 * @param Manager $manager Routing url manager.
	 */
	public function __construct(private Router $router)
	{
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 * 
	 * @return ResponseInterface
	 * @throws HttpException with status:
	 * - 500 request url path not matched to any routing rule
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$route = $request->getAttribute('route');
		if ($route instanceof Route) {
			return $handler->handle($request);
		}

		$base = $request->getAttribute('homeUrl', '');
		$uri = $request->getUri();
		$method = $request->getMethod();

		try {
			$route = $this->router->determineRoute($uri, $method, $base);
		} catch (InvalidRoutingException $e) {
			throw new HttpException(500, $e->getMessage(), 0, $e);
		}

		return $handler->handle($request->withAttribute('route', $route));
	}
}