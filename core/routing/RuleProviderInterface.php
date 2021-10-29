<?php

declare(strict_types=1);

namespace core\routing;

/**
 * Url routing rules provider interface.
 */
interface RuleProviderInterface
{
    /**
     * Get rules sorted by priority from higher to lower.
     * 
     * @return Rule[]
     */
    public function getRules(): iterable;

    /**
     * Add rule.
     * 
     * @param Rule $rule Rule to add
     * @param int|null $priority Rule priority. Less value means higher priority.
     * 
     * @return self
     */
    public function addRule(Rule $rule, int $priority): self;

    /**
     * Get rule by name
     * 
     * @param string $name Rule name
     * 
     * @return Rule
     * 
     * @throws InvalidRoutingException if rule not found in the provider
     */
    public function getRule(string $name): Rule;
}