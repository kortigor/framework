<?php

declare(strict_types=1);

namespace core\middleware;

use JsonException;
use InvalidArgumentException;
use core\helpers\Url;
use core\web\Response;
use core\web\Cart;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Cart handle middleware.
 */
class CartHandle implements MiddlewareInterface
{
    /**
     * Constructor.
     * 
     * @param string $cookieName Cart cookie name.
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
            $cart = $cookie ? Cart::fromCookie($cookie) : new Cart([]);
        } catch (JsonException | InvalidArgumentException) {
            $response = Response::createNew();
            $response->cookies->removeByName($this->cookieName);
            return $response->withRedirect(Url::current(), 302);
        }

        return $handler->handle($request->withAttribute($this->cookieName, $cart));
    }
}