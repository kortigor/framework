<?php

declare(strict_types=1);

namespace core\routing;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use core\helpers\ArrayHelper;

class Matcher
{
    /**
     * @var array
     */
    private const PLACEHOLDERS = [
        '{controller}',
        '{action}',
    ];

    /**
     * @var array Routing table data
     */
    private array $routes;

    /**
     * Constructor.
     * 
     * @param RuleProviderInterface $provider Routing rules provider
     * @param PsrCacheInterface|null $cache PSR-16 cache implementation to use compiled patterns caching, if null - no caching
     */
    public function __construct(private RuleProviderInterface $provider, ?PsrCacheInterface $cache = null)
    {
        if ($cache instanceof PsrCacheInterface) {
            $key = 'matcher_' . APP;
            if (!$this->routes = $cache->get($key, [])) {
                $this->compileRegex();
                $cache->set($key, $this->routes);
            }
        } else {
            $this->compileRegex();
        }
    }

    /**
     * Create compiled routing table REGEXPs to match urls.
     * 
     * @return void
     */
    private function compileRegex(): void
    {
        foreach ($this->provider->getRules() as $rule) {
            $regexp = (new patterns\Routing($rule))->toRegex();
            foreach ($rule->getMethods() as $method) {
                $this->routes[$method][$regexp] = [
                    $rule->getName(),
                    $rule->getRoute(),
                    $rule->getArguments()
                ];
            }
        }
    }

    /**
     * Find matched route for given http method and uri
     * 
     * @param string $method Http method.
     * @param string $uri Uri.
     * 
     * @return Route Route matched for given http method and uri.
     * @throws InvalidRoutingException If no routes matched
     */
    public function match(string $method, string $uri): Route
    {
        $uri = rtrim($uri, '/');
        $method = strtoupper($method);
        $route = $this->doMatch($method, $uri);

        return $route;
    }

    /**
     * Perform matching operation
     * 
     * @param string $method
     * @param string $uri
     * 
     * @return Route
     * @throws InvalidRoutingException If no routes matched
     */
    private function doMatch(string $method, string $uri): Route
    {
        foreach ($this->routes[$method] ?? [] as $pattern => $record) {
            list($name, $route, $arguments) = $record;

            // Check simple rule without placeholders in URL
            if ($uri === $pattern) {
                return $this->createRoute($name, $route, $arguments);
            }

            // Check matching to uri rule with placeholders in URL
            if (!preg_match('#^' . $pattern . '$#s', $uri, $matches)) {
                continue;
            }

            // Rule matched, so extract controller and action
            $controller = ArrayHelper::remove($matches, 'controller');
            $action = ArrayHelper::remove($matches, 'action');

            // URL contains controller
            if ($controller) {
                $route = str_replace(self::PLACEHOLDERS, [$controller, $action], $route);
            }

            // If rule contains arguments - pass them, else arguments from URL
            $arguments = $arguments ?: $this->processParameters($matches);
            return $this->createRoute($name, $route, $arguments);
        }

        throw new InvalidRoutingException('No suitable routes was found.');
    }

    /**
     * Create matched route.
     * 
     * @param string $name rule name
     * @param string $route
     * @param array $parameters
     * 
     * @return Route
     */
    private function createRoute(string $name, string $route, array $parameters): Route
    {
        $rule = $this->provider->getRule($name);
        return (new Route($route, $parameters))->setRule($rule);
    }

    /**
     * Remove "non associative" elements from route parameters
     * 
     * @param array $parameters
     * 
     * @return array
     */
    private function processParameters(array $parameters): array
    {
        foreach ($parameters as $k => $v) {
            if (is_int($k)) {
                unset($parameters[$k]);
            }
        }

        return $parameters;
    }
}