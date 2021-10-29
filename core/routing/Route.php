<?php

declare(strict_types=1);

namespace core\routing;

class Route
{
    /**
     * @var Rule
     */
    private Rule $rule;
    /**
     * @var string
     */
    private string $controller;
    /**
     * @var string
     */
    private string $action;

    /**
     * Constructor.
     * 
     * @param string $route Route in format of routing rules syntax: 'controller/action'.
     * @param array $parameters Arguments to pass to route action.
     * 
     * @return void
     */
    public function __construct(private string $route, private array $parameters = [])
    {
        if (!str_contains($route, '/')) {
            throw new InvalidRoutingException("Route record must contain '/' delimiter, '{$route}' given");
        }
        list($this->controller, $this->action) = explode('/', $this->route, 2);
    }

    /**
     * Set route parameter.
     * 
     * @param string $name Parameter name.
     * @param mixed $value Parameter value.
     * 
     * @return self
     */
    public function setParameter(string $name, mixed $value): self
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * Set route rule.
     * 
     * @param Rule $rule
     * 
     * @return self
     */
    public function setRule(Rule $rule): self
    {
        $this->rule = $rule;
        return $this;
    }

    /**
     * Get route rule.
     * 
     * @return Rule|null If null it means that route created not for matching rule in the Matcher
     */
    public function getRule(): ?Rule
    {
        return $this->rule ?? null;
    }

    /**
     * Set route value.
     * 
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->route;
    }

    /**
     * Get route parameters.
     * 
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get route controller.
     * 
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * Get route action.
     * 
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }
}