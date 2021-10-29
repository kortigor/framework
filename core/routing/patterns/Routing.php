<?php

declare(strict_types=1);

namespace core\routing\Patterns;

use core\routing\InvalidRoutingException;
use core\routing\patterns\Placeholders;

/**
 * Routing rule pattern converter for routing tasks.
 */
class Routing extends Abstraction
{
    public function toRegex(): string
    {
        $pattern = rtrim($this->rule->getPattern(), '/');

        if (!$this->rule->hasPlaceholder()) {
            return $pattern;
        }

        if (preg_match('#^/\{(\w+):(\w+):\?\}$#', $pattern)) {
            throw new InvalidRoutingException("Prefix required when use optional placeholder in pattern '{$pattern}'");
        }

        $regexp = preg_replace_callback('#/\{(\w+):(' . Placeholders::REGEX . '):\?\}$#', [$this, 'replaceOptionalPlaceholders'], $pattern);
        $regexp = preg_replace_callback('#\{(\w+):(' . Placeholders::REGEX . ')\}#', [$this, 'replacePlaceholders'], $regexp);

        return $regexp;
    }

    private function replaceOptionalPlaceholders(array $match): string
    {
        list(, $name, $pattern) = $match;
        return '(?:/(?<' . $name . '>' . strtr($pattern, Placeholders::TYPES) . '))?';
    }

    private function replacePlaceholders(array $match): string
    {
        list(, $name, $pattern) = $match;
        return '(?<' . $name . '>' . strtr($pattern, Placeholders::TYPES) . ')';
    }
}