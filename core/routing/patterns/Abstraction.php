<?php

declare(strict_types=1);

namespace core\routing\Patterns;

use core\routing\Rule;

/**
 * Abstract routing rule pattern converter to regexp.
 */
abstract class Abstraction
{
    /**
     * Constructor.
     * 
     * @param Rule $rule Routing rule.
     */
    public function __construct(protected Rule $rule)
    {
    }

    /**
     * Perform convert pattern to necessary regex.
     * 
     * @return string
     */
    abstract public function toRegex(): string;
}