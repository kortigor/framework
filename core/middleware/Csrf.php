<?php

declare(strict_types=1);

namespace core\middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use core\exception\HttpException;
use core\base\Security;
use core\web\Cookie;
use core\web\Response;
use core\web\ServerRequest;

/**
 * CSRF protect middleware.
 */
class Csrf implements MiddlewareInterface
{
	const PARAMETER_NAME = '_csrf';

	const HEADER_NAME = 'X-CSRF-Token';

	const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

	/**
	 * @var string
	 */
	private string $parameterName;

	/**
	 * @var ServerRequest
	 */
	private ServerRequest $request;

	/**
	 * @var string Current CSRF token
	 */
	private string $token;

	/**
	 * @var Cookie Cookie to store generated CSRF token
	 */
	private Cookie $cookie;

	/**
	 * Constructor.
	 * 
	 * @param bool $isEnabled Whether CSRF proteсtion enabled
	 * @param string $appId Application ID to customize parameter name
	 * @param Security $security Security object instance
	 */
	public function __construct(private bool $isEnabled, string $appId, private Security $security)
	{
		$this->parameterName = static::PARAMETER_NAME . '-' . $appId;
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if (!$this->isEnabled) {
			return $handler->handle($request);
		}

		/** @var ServerRequest $request */
		$this->request = $request;

		// If token invalid break request and send invalid response
		if (!$this->validateToken($request)) {
			throw new HttpException(400, 'Invalid CSRF.');
		}

		// Add CSRF attribute and header to request
		$request = $request
			->withAttribute('csrfParam', $this->parameterName)
			->withAttribute($this->parameterName, $this->getToken())
			->withAddedHeader(static::HEADER_NAME, $this->getToken());

		// If no new cookie with CSRF token, just pass to the next
		if (!isset($this->cookie)) {
			return $handler->handle($request);
		}

		// Capture response and send new CSRF token cookie
		/** @var Response $response */
		$response = $handler->handle($request);
		$response->cookies->add($this->cookie);
		return $response;
	}

	/**
	 * Returns the token used to perform CSRF validation.
	 *
	 * This token is generated in a way to prevent [BREACH attacks](http://breachattack.com/). It may be passed
	 * along via a hidden field of an HTML form or an HTTP header value to support CSRF validation.
	 * @param bool $regenerate whether to regenerate CSRF token. When this parameter is true, each time
	 * this method is called, a new CSRF token will be generated and persisted (in session or cookie).
	 * @return string the token used to perform CSRF validation.
	 */
	public function getToken(bool $regenerate = false): string
	{
		if (!isset($this->token) || $regenerate) {
			$token = $this->loadToken();
			if ($regenerate || empty($token)) {
				$token = $this->generateToken();
			}
			$this->token = $this->security->maskToken($token);
		}

		return $this->token;
	}

	/**
	 * Validate token
	 * 
	 * @param ServerRequestInterface $request
	 * 
	 * @return bool true if valid
	 */
	private function validateToken(ServerRequestInterface $request): bool
	{
		if (in_array($request->getMethod(), static::SAFE_METHODS, true)) {
			return true;
		}

		$token = $this->getTokenFromRequest($request);
		$trueToken = $this->getToken();

		return !empty($token)
			&& $this->security->compareString($this->security->unmaskToken($token), $this->security->unmaskToken($trueToken));
	}

	/**
	 * Loads the CSRF token from cookie.
	 * 
	 * @return string the CSRF token loaded from cookie.
	 * Empty string is returned if the cookie does not have CSRF token.
	 */
	private function loadToken(): string
	{
		return $this->request->cookies->getValue($this->parameterName, '');
	}

	/**
	 * Get token from request
	 * 
	 * @param ServerRequestInterface $request
	 * 
	 * @return string
	 */
	private function getTokenFromRequest(ServerRequestInterface $request): string
	{
		$parsedBody = $request->getParsedBody();

		/** @var mixed $token */
		$token = $parsedBody[$this->parameterName] ?? '';
		if (empty($token)) {
			$headers = $request->getHeader(static::HEADER_NAME);
			$token = reset($headers);
		}

		return is_string($token) ? $token : '';
	}

	/**
	 * Generates an unmasked random token used to perform CSRF validation.
	 * 
	 * @return string the random token for CSRF validation.
	 */
	private function generateToken(): string
	{
		$token = $this->security->generateRandomString();
		$this->cookie = $this->createCookie($token);
		return $token;
	}

	/**
	 * Creates a cookie with a given CSRF token.
	 * 
	 * @param string $token the CSRF token
	 * @return Cookie the generated cookie
	 */
	private function createCookie(string $token): Cookie
	{
		return (new Cookie($this->parameterName, $token))->setHttpOnly(true);
	}
}