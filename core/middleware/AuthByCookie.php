<?php

declare(strict_types=1);

namespace core\middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use core\auth\AuthAgentByCookie;
use core\web\User;
use core\web\Response;
use core\web\ServerRequest;

/**
 * Middleware to auth user by cookies.
 */
class AuthByCookie implements MiddlewareInterface
{
    /**
     * @param string $identityClass Class name implements `IdentityInterface` used to authorization
     * @param string $cookieName Cookie name where authorization data stored in.
     * @param User $user System user object to authorize.
     */
    public function __construct(private string $identityClass, private string $cookieName, private User $user)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var ServerRequest $request */
        if (!$request->cookies->has($this->cookieName)) {
            return $handler->handle($request);
        }

        $auth = false;
        $cookie = $request->cookies->get($this->cookieName);
        $agent = new AuthAgentByCookie($cookie, $this->identityClass);
        $identity = $agent->getIdentity();
        if ($identity) {
            $this->user->login($identity);
            $auth = true;
        }

        // Capture response to send cookie
        /** @var Response $response */
        $response = $handler->handle($request);
        if ($auth) {
            $response->cookies->add($agent->getAuthCookie());
        } else {
            $response->cookies->removeByName($this->cookieName);
        }

        return $response;
    }
}