<?php

declare(strict_types=1);

namespace core\middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use core\middleware\formatter\Manager;
use core\web\Response;

/**
 * Format response.
 */
class ContentFormatter implements MiddlewareInterface
{
    /**
     * Constructor.
     * 
     * @param Manager $manager Response formatter manager
     */
    public function __construct(private Manager $manager)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Response $response */
        $response = $handler->handle($request);
        // Status code 204: No Content.
        if ($response->getStatusCode() === 204) {
            $response->getBody()->close();
            return $response;
        }

        $response = $this->manager->format($response, $response->getFormat());
        return $response;
    }
}