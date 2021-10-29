<?php

declare(strict_types=1);

namespace core\validators;

use Closure;

/**
 * Represents functionality to normalize single attribute.
 */
final class AttributeNormalizator
{
    /**
     * Attribute name to validate.
     */
    public string $attribute;

    /**
     * Normalization function to execute.
     */
    protected Closure $callback;

    /**
     * Constructor.
     * 
     * @param string $attribute
     * @param Closure $callback
     */
    public function __construct(string $attribute, Closure $callback)
    {
        $this->attribute = $attribute;
        $this->callback = $callback;
    }

    /**
     * Execute normalizator.
     * 
     * @param mixed $value
     * 
     * @return mixed normalized value
     */
    public function run($value)
    {
        return ($this->callback)($value);
    }
}