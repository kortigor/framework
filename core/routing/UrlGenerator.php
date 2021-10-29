<?php

declare(strict_types=1);

namespace core\routing;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

/**
 * Generate urls based on routing rules patterns.
 */
class UrlGenerator
{
    /**
     * @var bool Whether to add trailing slash in generated urls.
     */
    public bool $trailingSlash = true;

    /**
     * @var array
     */
    private array $map;
    /**
     * @var array
     */
    private array $mapData;
    /**
     * @var array
     */
    private array $mapOptionalData;
    /**
     * @var string
     */
    private string $base;

    /**
     * Constructor.
     * 
     * @param string $host Site host (https://example.com) for absolute Urls.
     * @param RuleProviderInterface $provider Routing rules provider.
     * @param PsrCacheInterface|null $cache PSR-16 cache implementation to use compiled patterns caching, if null - no caching
     */
    public function __construct(private string $host, private RuleProviderInterface $provider, ?PsrCacheInterface $cache = null)
    {
        if ($cache instanceof PsrCacheInterface) {
            $key = 'generator_' . APP;
            if (($data = $cache->get($key)) !== null) {
                list($this->map, $this->mapData, $this->mapOptionalData) = $data;
            } else {
                $this->compileRegex();
                // preCompile data
                foreach ($this->map as $name => $v) {
                    $this->compileData($name);
                }

                $cache->set($key, [$this->map, $this->mapData, $this->mapOptionalData]);
            }
        } else {
            $this->compileRegex();
        }
    }

    /**
     * Create compiled REGEX to generate urls.
     * 
     * @return void
     */
    private function compileRegex(): void
    {
        foreach ($this->provider->getRules() as $rule) {
            $this->addRule($rule);
        }
    }

    /**
     * Add rule to generator.
     * 
     * @param Rule $rule
     * 
     * @return self
     */
    public function addRule(Rule $rule): self
    {
        $this->map[$rule->getName()] = (new patterns\Generating($rule))->toRegex();
        return $this;
    }

    /**
     * Check routing rule exists.
     * 
     * @param string $name Pattern name to check.
     * 
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->map[$name]);
    }

    /**
     * Create relative url by route rule.
     *
     * @param string $name Routing rule name.
     * @param array $parameters Rule parameters.
     * 
     * @return string Created url.
     * @throws InvalidUrlArgumentException
     */
    public function create(string $name, array $parameters = []): string
    {
        if (!$this->has($name)) {
            throw new InvalidUrlArgumentException("Routing rule {$name} not exist.");
        }

        $this->compileData($name);
        $this->assertParameters($name, $parameters);

        $replacePairs = []; // Pairs to replace in compiled url patterns
        $queryParams = []; // Url query parameters
        $ancor = '';

        // Collect necessary data
        foreach ($parameters as $param => $value) {
            if ($param === '#') {
                $ancor = $param . $value;
            } elseif (isset($this->mapData[$name][$param])) {
                $from = '(:' . $param . ')';
                $replacePairs[$from] = $value;
            } elseif (isset($this->mapOptionalData[$name][$param])) {
                $from = '(:' . $param . ':?)';
                $replacePairs[$from] = $value;
            } else {
                $queryParams[$param] = $value;
            }
        }

        // Compiled url pattern
        $pattern = $this->map[$name];
        // Replace placeholders with real data
        $url = strtr($pattern, $replacePairs);
        // Clean blank optional placeholder, if exist
        if (strpos($url, '/(:') !== false) {
            $url = preg_replace('#/\(:(\w+):\?\)$#', '', $url);
        }

        // Ensure to have starting and no trailing slash
        $url = '/' . trim($url, '/');

        // Add trailing slash if needed
        if ($this->trailingSlash) {
            $url .= '/';
        }

        // Add url base 
        if ($this->base) {
            $url = '/' . $this->base . $url;
        }

        // Ensure to clean possible double slashes
        $url = str_replace('//', '/', $url);

        $query = urldecode(http_build_query($queryParams));
        if ($query) {
            $url .= '?' . $query;
        }

        $url .= $ancor;

        return $url;
    }

    /**
     * Create absolute url by route rule.
     *
     * @param string $name Routing rule name.
     * @param array $parameters Rule parameters.
     * 
     * @return string Created absolute url.
     * @throws InvalidUrlArgumentException
     */
    public function createAbsolute(string $name, array $parameters = []): string
    {
        return rtrim($this->host, '/') . $this->create($name, $parameters);
    }

    /**
     * Set site host.
     * 
     * @param string $host
     * 
     * @return self
     */
    public function setHost(string $host): self
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Set Url base.
     * 
     * @param string $base
     * 
     * @return self
     */
    public function setBase(string $base): self
    {
        $this->base = trim($base, '/');
        return $this;
    }

    /**
     * Transform pattern to generator data.
     * 
     * @param string $name
     * 
     * @return void
     */
    private function compileData(string $name): void
    {
        if (isset($this->mapData[$name])) {
            return;
        }

        $pattern = $this->map[$name];
        $matches = [];

        $this->mapData[$name] = [];
        $this->mapOptionalData[$name] = [];

        if (preg_match_all('#\(:(\w+)\)#', $pattern, $matches)) {
            $this->mapData[$name] = array_flip($matches[1]);
        }

        if (preg_match_all('#/\(:(\w+):\?\)$#', $pattern, $matches)) {
            $this->mapOptionalData[$name] = array_flip($matches[1]);
        }
    }

    /**
     * Assert required parameters exist.
     * 
     * @param string $name Routing rule name
     * @param array $parameters Rule parameters.
     * 
     * @return void
     * @throws InvalidUrlArgumentException If some parameters missed.
     */
    private function assertParameters(string $name, array $parameters): void
    {
        $diff = array_diff_key($this->mapData[$name], $parameters);

        if ($diff) {
            $missing = implode('", "', array_keys($diff));
            throw new InvalidUrlArgumentException(sprintf('Rule "%s" has missing parameters ("%s").', $name, $missing));
        }
    }
}