<?php

declare(strict_types=1);

namespace core\middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use core\helpers\Url;
use core\routing\Route;
use core\exception\HttpException;
use core\web\Cookie;
use core\web\User;
use core\web\Response;
use core\web\ServerRequest;

/**
 * Middleware to require user be authorized.
 */
class AuthRequire implements MiddlewareInterface
{
    /**
     * @var string the login url to redirect if user unauthorized.
     * @see \core\routing\Route
     */
    public string $loginUrl = 'login/';

    /**
     * Means make 302 redirect to login form (default) or take control directly
     * to controller responsible for login (i.e. 'LoginController')
     * @var boolean
     */
    public bool $loginRequireByRedirect = true;

    /**
     * @var bool whether to check if the request is an AJAX request. When this is true and the request
     * is an AJAX request, the current URL (for AJAX request) will NOT be set as the return URL.
     */
    public bool $checkAjax = true;

    /**
     * @var bool whether to stop application execution for non authorized users
     */
    public bool $breakIfNotAuth = false;

    /**
     * @var int
     */
    private int $returnUrlCookieDuration = 60 * 60 * 365;

    /**
     * Constructor.
     * 
     * @param string $returnUrlCookieName Cookie name used to store the value of `returnUrl`.
     * @param User $user System user object to check authorization.
     */
    public function __construct(private string $returnUrlCookieName, private User $user)
    {
    }

    /**
     * Redirects the user browser to the login page.
     *
     * Before the redirection, the current URL (if it's not an AJAX url) will be kept as `$returnUrl` so that
     * the user browser may be redirected back to the current page after successful login.
     *
     * Make sure you set `$loginUrl` so that the user browser can be redirected to the specified login URL after
     * calling this method.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * 
     * @return ResponseInterface
     * @throws HttpException The "Access Denied" HTTP exception if `$loginUrl` is not set
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var ServerRequest $request */

        // Logged in or request to login url, go forward
        if ($this->user->isAuth || strstr($request->getUri()->getPath(), $this->loginUrl)) {
            return $handler->handle($request);
        }

        // Login url does not set, or set break for non authorized
        if (!$this->loginUrl || $this->breakIfNotAuth) {
            throw new HttpException(403, 'Login Required');
        }

        // Login by redirect
        if ($this->loginRequireByRedirect) {
            // Normally no redirect for ajax, just send error with appropriate status code
            if ($this->checkAjax && $request->isAjax()) {
                throw new HttpException(403, 'Login Required');
            }

            // Remember return URL in cookie, where browser should be redirected to after successful login.
            $returnUrl = (string) $request->getUri();
            $cookie = new Cookie($this->returnUrlCookieName, $returnUrl);
            $cookie->setMaxAge($this->returnUrlCookieDuration);
            $response = Response::createNew();
            $response->cookies->add($cookie);

            return $response->withRedirect(Url::to([$this->loginUrl]), 302);
        }

        // Login without redirect
        // Say to 'Routing' middleware to use login controller route
        $route = new Route($this->loginUrl);
        $request = $request->withAttribute('route', $route);
        return $handler->handle($request);
    }
}