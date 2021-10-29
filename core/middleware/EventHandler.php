<?php

declare(strict_types=1);

namespace core\middleware;

use core\event\Manager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

class EventHandler implements MiddlewareInterface
{
	const HANDLER_REQUEST_ATTRIBUTE = 'eventManager';

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		// Add event manager attribute to request
		$request = $request->withAttribute(self::HANDLER_REQUEST_ATTRIBUTE, new Manager);

		// Capture response
		$response = $handler->handle($request);

		// Get all events written into event manager and dispatch them all.
		/** @var Manager $manager */
		$manager = $request->getAttribute(self::HANDLER_REQUEST_ATTRIBUTE);
		$events = $manager->releaseEvents();
		$manager->dispatchAll($events);

		return $response;
	}
}