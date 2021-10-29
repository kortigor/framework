<?php

declare(strict_types=1);

namespace core\middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Symfony\Component\DomCrawler\Crawler;
use core\exception\HttpException;
use core\helpers\Url;
use core\web\Response;
use core\web\ServerRequest;
use core\web\ContentType;

/**
 * Pjax request filter.
 * 
 * @see https://github.com/defunkt/jquery-pjax
 * @see https://github.com/yiisoft/jquery-pjax
 */
class Pjax implements MiddlewareInterface
{
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		/** @var ServerRequest $request */
		// Pass through non pjax request
		if (!$request->isPjax()) {
			return $handler->handle($request);
		}

		/** @var Response $response */
		$response = $handler->handle($request);

		// Do nothing with redirection or non html format responses
		if ($response->isRedirection() || $response->getFormat() !== ContentType::FORMAT_HTML) {
			return $response;
		}

		$container = $request->getHeaderLine('X-PJAX-CONTAINER');
		if (!$container) {
			throw new HttpException(422, 'No PJAX container');
		}

		$url = Url::getRelative($request->getUri());
		$crawler = new Crawler($response->getBodyData());
		$title = $this->getTitle($crawler);
		$content = $this->getContents($crawler, $container);
		$version = $this->getVersion($crawler);

		$response = $response->withBodyData($title . $content)->withHeader('X-PJAX-URL', $url);

		if ($version) {
			$response = $response->withHeader('X-PJAX-Version', $version);
		}

		return $response;
	}

	/**
	 * Prepare an HTML title tag.
	 *
	 * @param Crawler $crawler
	 * @return string
	 */
	private function getTitle(Crawler $crawler): string
	{
		$pageTitle = $crawler->filter('head > title')->html();

		return '<title>' . $pageTitle . '</title>';
	}

	/**
	 * Fetch the PJAX-specific HTML from the response.
	 *
	 * @param Crawler $crawler
	 * @param string $container
	 * @return string
	 */
	private function getContents(Crawler $crawler, string $container): string
	{
		$content = $crawler->filter($container);
		if (!$content->count()) {
			throw new HttpException(422, 'No PJAX content or invalid PJAX container');
		}

		return $content->html();
	}

	/**
	 * Get layout version from http-equiv="x-pjax-version" meta tag.
	 * 
	 * @param Crawler $crawler
	 * 
	 * @return string
	 */
	private function getVersion(Crawler $crawler): string
	{
		$node = $crawler->filter('head > meta[http-equiv="x-pjax-version"]');
		if ($node->count()) {
			return $node->attr('content');
		}

		return '';
	}
}