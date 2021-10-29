<?php

declare(strict_types=1);

namespace core\routing;

/**
 * Url routing rule.
 */
class Rule
{
    const PRIORITY_TOP = 10;

    const PRIORITY_STANDARD = 20;

    const PRIORITY_FINAL = 30;

    /**
     * @var array Allowed HTTP methods.
     * @see https://tools.ietf.org/html/rfc7231#section-4.1
     */
    private const HTTP_METHODS = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH',
        'HEAD',
        'CONNECT',
        'TRACE'
    ];

    /**
     * @var array Allowed request methods.
     */
    private array $methods;

    /**
     * Constructor.
     * 
     * @param string $name Rule name.
     * @param string $pattern Url pattern. If needed, with arguments placeholders.
     * @param string $route Called controller and action if rule matched, in 'controller/action' format.
     * @param array $arguments Arguments to pass in action method. If set, it rewrites arguments from url.
     * @param string $methods Allowed request methods, separated by '|', i.e. 'GET|POST'. Set to '*', if any method allowed.
     */
    public function __construct(
        private string $name,
        private string $pattern,
        private string $route,
        private array $arguments = [],
        string $method = 'GET|POST',
    ) {
        if (!str_contains($route, '/')) {
            throw new InvalidRoutingException(sprintf('Route must contain "/" delimiter, "%s" given', $route));
        }

        $this->methods = $this->convertMethods($method);
    }

    /**
     * Get the value of name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the value of methods
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get the value of route
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * Get the value of arguments
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Get rule's url pattern.
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Whether rule contains request method.
     * 
     * @param string $method Method name
     * 
     * @return bool
     */
    public function hasMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods);
    }

    /**
     * Whether rule contains placeholders
     * (controller aliases, actions or parameters in URL)
     * 
     * @return bool
     */
    public function hasPlaceholder(): bool
    {
        return str_contains($this->pattern, '{');
    }

    private function convertMethods(string $method): array
    {
        $methods = strtoupper($method);
        if ($methods === '*') {
            $methods = self::HTTP_METHODS;
        } elseif (strpos($methods, '|')) {
            $methods = explode('|', $methods);
        }

        $methods = (array) $methods;
        foreach ($methods as $method) {
            $this->assertMethodValid($method);
        }

        return $methods;
    }

    private function assertMethodValid(string $method): void
    {
        if (!in_array($method, self::HTTP_METHODS)) {
            throw new InvalidRoutingException(
                sprintf('Request method must be one of %s, "%s" given', implode(',', self::HTTP_METHODS), $method)
            );
        }
    }
}