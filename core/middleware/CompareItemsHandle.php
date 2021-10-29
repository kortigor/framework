<?php

declare(strict_types=1);

namespace core\middleware;

use JsonException;
use InvalidArgumentException;
use core\helpers\Url;
use core\web\Response;
use core\web\CompareItems;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Compare items handle middleware.
 */
class CompareItemsHandle implements MiddlewareInterface
{
    /**
     * Constructor.
     * 
     * @param string $cookieName Compare cookie name.
     */
    public function __construct(private string $cookieName)
    {
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * 
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var \core\web\ServerRequest $request */
        $cookie = $request->cookies->get($this->cookieName);
        try {
            $compare = $cookie ? CompareItems::fromCookie($cookie) : new CompareItems([]);
        } catch (JsonException | InvalidArgumentException) {
            $response = Response::createNew();
            $response->cookies->removeByName($this->cookieName);
            return $response->withRedirect(Url::current(), 302);
        }

        return $handler->handle($request->withAttribute($this->cookieName, $compare));
    }
}