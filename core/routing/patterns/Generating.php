<?php

declare(strict_types=1);

namespace core\routing\Patterns;

/**
 * Routing rule pattern converter for url generating tasks.
 */
class Generating extends Abstraction
{
    public function toRegex(): string
    {
        // Optional placeholders
        $regexp = preg_replace('#\{(\w+):(' . Placeholders::REGEX . '):\?\}#i', '(:$1:?)', $this->rule->getPattern());
        // Required placeholders
        $regexp = preg_replace('#\{(\w+):(' . Placeholders::REGEX . ')\}#i', '(:$1)', $regexp);

        return $regexp;
    }
}