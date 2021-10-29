<?php

declare(strict_types=1);

namespace core\link\template\node;

use InvalidArgumentException;

/**
 * Variable node implementation
 */
class Variable extends Abstraction
{
    /**
     * @var array Valid variable modifiers.
     */
    const VALID_MODIFIERS = [
        ':',
        '*',
        '%',
    ];

    /**
     * @var string Variable name without modifier e.g. 'term:1' becomes 'term'
     */
    public string $name;

    /**
     * Constructor
     *
     * @param string $token
     * @param mixed $modifier Variable modifier
     * @param mixed $value Variable value
     *
     * @return void
     */
    public function __construct(string $token, public string $modifier = '', public mixed $value = null)
    {
        if ($modifier && !in_array($modifier, static::VALID_MODIFIERS)) {
            throw new InvalidArgumentException("Invalid modifier '{$modifier}'");
        }

        parent::__construct($token);

        // normalize var name e.g. from 'term:1' becomes 'term'
        $name = $token;
        if ($modifier === ':') {
            $name = substr($name, 0, strpos($name, $modifier));
        }

        $this->name = $name;
    }
}