<?php

declare(strict_types=1);

namespace core\validators;

use core\helpers\ArrayHelper;

/**
 * Represents functionality for client side validation for single model's attribute.
 * For Jquery validation plugin.
 * 
 * @see https://jqueryvalidation.org/
 */
final class ClientAttributeValidator
{
    /**
     * @var string Attribute name to validate.
     */
    public string $attribute;

    /**
     * @var string Validator error message.
     */
    public string $message;

    /**
     * @var string Form related input name
     */
    public string $inputName;

    /**
     * @var string Form related input id
     */
    public string $inputId;

    /**
     * @var array Validator options
     */
    private array $options = [];

    /**
     * @var string Validation rule name
     */
    private string $method;

    /**
     * @var mixed Validator arguments
     */
    private array $arguments;

    /**
     * Constructor.
     * 
     * @param string $attribute Attribute name.
     * @param array $validator Validator rule.
     * Array with one rule name or associative array with rule name and arguments
     * Should be like:
     * ```
     * ['required'];
     * ['rangelength' => [1,2]];
     * ['maxlength' => 5];
     * ```
     * @param array $options Validator options.
     */
    public function __construct(string $attribute, array $validator, array $options = [])
    {
        $this->attribute = $attribute;
        $this->message = ArrayHelper::remove($options, 'message', '');
        $this->options = $options;

        $key = array_key_first($validator);
        $this->method = is_int($key) ? $validator[$key] : $key;
        $this->arguments = is_int($key) ? [] : [$validator[$key]];
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function __get($property)
    {
        return $this->options[$property] ?? null;
    }

    public function __set($property, $value)
    {
        $this->options[$property] = $value;
    }
}