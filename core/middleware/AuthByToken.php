<?php

declare(strict_types=1);

namespace core\middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use core\auth\AuthAgentByToken;
use core\web\User;
use core\web\ServerRequest;

/**
 * Middleware to auth user by access token.
 */
class AuthByToken implements MiddlewareInterface
{
    /**
     * @param string $identityClass Class name implements `IdentityInterface` used to authorization
     * @param string $tokenName Name of GET parameter contains authorization token.
     * @param User $user System user object to authorize.
     */
    public function __construct(private string $identityClass, private string $tokenName, private User $user)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var ServerRequest $request */
        $token = $request->get($this->tokenName, null) ?? $request->post($this->tokenName, null);
        if ($token) {
            $agent = new AuthAgentByToken($token, $this->identityClass);
            $identity = $agent->getIdentity();
            if ($identity) {
                $this->user->login($identity);
            }
        }

        return $handler->handle($request);
    }
}