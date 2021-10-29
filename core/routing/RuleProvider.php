<?php

declare(strict_types=1);

namespace core\routing;

/**
 * Url routing rules provider.
 */
class RuleProvider implements RuleProviderInterface
{
    /**
     * @var array<int, Rule[]>
     */
    protected iterable $rules = [];

    /**
     * @inheritDoc
     */
    public function getRules(): iterable
    {
        $priorities = array_keys($this->rules);
        sort($priorities);
        foreach ($priorities as $priority) {
            yield from $this->rules[$priority];
        }
    }

    /**
     * @inheritDoc
     */
    public function addRule(Rule $rule, int $priority): self
    {
        $this->rules[$priority][] = $rule;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRule(string $name): Rule
    {
        foreach ($this->getRules() as $rule) {
            if ($rule->getName() === $name) {
                return $rule;
            }
        }

        throw new InvalidRoutingException("Unable to get rule '{$name}'");
    }

    /**
     * Clears all rules.
     * 
     * @return self
     */
    public function clear(): self
    {
        $this->rules = [];
        return $this;
    }
}