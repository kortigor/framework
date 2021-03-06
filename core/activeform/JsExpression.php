<?php

declare(strict_types=1);

namespace core\activeform;

/**
 * JsExpression marks a string as a JavaScript expression.
 *
 * When using `\core\helpers\Json::encode()` or `\core\helpers\Json::htmlEncode()` to encode a value, JsonExpression objects
 * will be specially handled and encoded as a JavaScript expression instead of a string.
 */
class JsExpression
{
    /**
     * @var string the JavaScript expression represented by this object
     */
    public string $expression;

    /**
     * Constructor.
     * @param string $expression the JavaScript expression represented by this object
     */
    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    /**
     * The PHP magic function converting an object into a string.
     * @return string the JavaScript expression.
     */
    public function __toString()
    {
        return $this->expression;
    }
}