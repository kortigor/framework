<?php

declare(strict_types=1);

namespace core\middleware;

use Locale;
use Sys;
use core\web\Response;
use core\web\Cookie;
use core\http\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Language content negotiator.
 */
class NegotiatorLanguage implements MiddlewareInterface
{
    /**
     * @var string Parameter name (query parameter or cookie name) handles site language value.
     */
    public string $langParameter = 'lang';

    /**
     * @var array Application supported languages.
     */
    public array $supportedLangs = ['ru', 'en'];

    /**
     * Constructor.
     * 
     * @param string $defaultLang Default application language.
     * @param string $cookieLifeTime Language cookie life time.
     */
    public function __construct(private string $defaultLang, private int $cookieLifeTime = 0)
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
        if ($lang = $this->fromQuery($request)) {
            $lang = $this->normalize($lang);
            $response = Response::createNew();
            $cookie = (new Cookie($this->langParameter, $lang))->setExpiryTime($this->cookieLifeTime);
            $response->cookies->add($cookie);
            $redirect = Uri::withoutQueryValue($request->getUri(), $this->langParameter);

            return $response->withRedirect((string) $redirect, 302);
        }

        $lang = $this->fromCookies($request) ?? $this->fromHeader($request) ?? Sys::$app->language;
        $lang = $this->normalize($lang);
        Sys::$app->setLocale($lang);

        return $handler->handle($request);
    }

    /**
     * Normalize lang to supported value.
     * 
     * @param string $lang
     * 
     * @return string
     */
    private function normalize(string $lang): string
    {
        $lang = strtolower($lang);
        return in_array($lang, $this->supportedLangs) ? $lang : $this->defaultLang;
    }

    /**
     * Get language from request's 'Accept-Language' header
     * 
     * @param ServerRequestInterface $request
     * 
     * @return string|null
     */
    private function fromHeader(ServerRequestInterface $request): ?string
    {
        $accept = $request->getHeaderLine('Accept-Language');
        if (!$accept) {
            return null;
        }

        $locale = Locale::acceptFromHttp($accept);
        if ($locale === false) {
            return null;
        }

        $language = Locale::getPrimaryLanguage($locale);
        return $language ?: null;
    }

    /**
     * Get language from cookies
     * 
     * @param ServerRequestInterface $request
     * 
     * @return string|null
     */
    private function fromCookies(ServerRequestInterface $request): ?string
    {
        /** @var \core\web\ServerRequest $request */
        $language = $request->cookies->getValue($this->langParameter);
        return $language;
    }

    /**
     * Get language from request query.
     * 
     * @param ServerRequestInterface $request
     * 
     * @return string|null
     */
    private function fromQuery(ServerRequestInterface $request): ?string
    {
        /** @var \core\web\ServerRequest $request */
        $language = $request->getQueryParam($this->langParameter);
        return $language;
    }
}