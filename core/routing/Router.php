<?php

declare(strict_types=1);

namespace core\routing;

use core\helpers\Url;
use Psr\Http\Message\UriInterface;
use Psr\SimpleCache\CacheInterface;

class Router
{
	/**
	 * @var bool Whether use compiled url patterns cache.
	 */
	public bool $useCache = true;

	/**
	 * @var string Cache directory where cache located is.
	 * 
	 * @see getMatcher()
	 * @see getGenerator()
	 */
	public string $cacheDir;

	/**
	 * @var Matcher
	 */
	private Matcher $matcher;

	/**
	 * @var UrlGenerator
	 */
	private UrlGenerator $generator;

	/**
	 * @var UriInterface
	 */
	private UriInterface $uri;

	/**
	 * @var CacheInterface|null PSR cache instance.
	 */
	private ?CacheInterface $cache;

	/**
	 * Constructor.
	 * 
	 * @param RuleProviderInterface $provider Routing rules provider instance.
	 * @param array $rules Custom routing rules records.
	 */
	public function __construct(private RuleProviderInterface $provider, array $rules = [])
	{
		$collection = BaseRules::collection();
		$collection[Rule::PRIORITY_STANDARD] = $rules;

		foreach ($collection as $priority => $records) {
			foreach ($records as $record) {
				$rule = new Rule(...$record);
				$this->provider->addRule($rule, $priority);
			}
		}
	}

	/**
	 * Set current request uri.
	 * 
	 * @param UriInterface $uri Uri contains current site host.
	 * 
	 * @return self
	 */
	public function setUri(UriInterface $uri): self
	{
		$this->uri = $uri;
		return $this;
	}

	/**
	 * Rule provider getter.
	 * 
	 * @return RuleProviderInterface
	 */
	public function getRuleProvider(): RuleProviderInterface
	{
		return $this->provider;
	}

	/**
	 * Retrieve appropriate route from routing rules.
	 * 
	 * @param UriInterface $uri Request uri
	 * @param string $method Request method
	 * @param string $base Starting part of url path not participating in routing.
	 * 
	 * For example: site backend called from 'somesite.ru/admin/',
	 * so no need to use '/admin/' part in routing, set argument as 'admin' or 'admin/' or '/admin/'
	 * 
	 * @return Route Route calculated from request by routing rules.
	 * @throws InvalidRoutingException If no routes matched
	 * @see Url::getRealRootPath()
	 */
	public function determineRoute(UriInterface $uri, string $method, string $base = ''): Route
	{
		$path = Url::getRealRootPath($uri, $base);
		$this->assertURLPath($path);
		return $this->getMatcher()->match($method, $path);
	}

	/**
	 * Url matcher getter.
	 * 
	 * @return Matcher
	 */
	public function getMatcher(): Matcher
	{
		if (!isset($this->matcher)) {
			$this->matcher = new Matcher($this->provider, $this->getCache());
		}

		return $this->matcher;
	}

	/**
	 * Url generator getter.
	 * 
	 * @return UrlGenerator
	 */
	public function getGenerator(): UrlGenerator
	{
		if (!isset($this->generator)) {
			$this->generator = new UrlGenerator(Url::getShemeHost($this->uri), $this->provider, $this->getCache());
		}

		return $this->generator;
	}

	/**
	 * Cache instance getter.
	 * 
	 * @return CacheInterface|null
	 */
	private function getCache(): ?CacheInterface
	{
		if (!isset($this->cache)) {
			$this->cache = $this->useCache ? new Cache($this->cacheDir, objectHash($this->provider)) : null;
		}

		return $this->cache;
	}


	/**
	 * PHP doesn't actually have a URL path validation function but it does have a URL validation function.
	 * So, to validate a path all we need to prepend any domain onto our path.
	 * 
	 * Note: to be a path it should start with a forward slash. 
	 * 
	 * @param string $path Url path to validate.
	 * 
	 * @throws InvalidRoutingException if url unvalid
	 */
	private function assertURLPath(string $path): void
	{
		if ($path[0] !== '/' || filter_var('http://foo.com' . $path, FILTER_VALIDATE_URL) === false) {
			throw new InvalidRoutingException(sprintf('Invalid url path "%s"', $path));
		}
	}
}