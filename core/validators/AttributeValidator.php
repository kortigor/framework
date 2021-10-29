<?php

declare(strict_types=1);

namespace core\validators;

use Closure;
use core\helpers\ArrayHelper;

/**
 * Represents functionality to server side validtion for single model's attribute.
 */
final class AttributeValidator
{
    /**
     * @var bool Whether to enable client-side validation for this validator.
     * The actual client-side validation is done via the JavaScript code returned
     * by `clientValidateAttribute()`. If that method returns null, even if this property
     * is true, no client-side validation will be done by this validator.
     */
    public bool $enableClientValidation = true;

    /**
     * @var string Validator error message.
     */
    public string $message;

    /**
     * @var string Attribute name to validate.
     */
    public string $attribute;

    /**
     * @var array Scenarios that the validator can be applied to.
     */
    public array $on = [];

    /**
     * @var array Scenarios that the validator should not be applied to.
     */
    public array $except = [];

    /**
     * @var string Default error message if message not set by rule or validator.
     */
    protected string $defaultMessage = 'Invalid attribute value';

    /**
     * @var array Validator options
     */
    protected array $options = [];

    /**
     * @var string Validator name
     */
    protected string $name;

    /**
     * @var bool
     */
    protected bool $isValid = false;

    /**
     * Validation function to execute
     */
    protected Closure $callback;

    /**
     * Callback arguments
     * 
     * If exists, used only to insert in sprintf pattern
     * 
     * @see `$callback`
     */
    protected array $arguments = [];

    /**
     * Constructor.
     * 
     * @param string $attribute Attribute name.
     * @param Closure $callback Function to validate
     * @param array $options Validator options.
     */
    public function __construct(string $attribute, Closure $callback, array $options = [])
    {
        $this->attribute = $attribute;
        $this->callback = $callback;
        $this->name = ArrayHelper::remove($options, 'name', '');
        $this->message = ArrayHelper::remove($options, 'message', $this->defaultMessage);
        $this->on = (array) ArrayHelper::remove($options, 'on', []);
        $this->except = (array) ArrayHelper::remove($options, 'except', []);
        $this->options = $options;

        $arguments = ArrayHelper::remove($options, 'arguments', []);
        foreach ($arguments as $arg) {
            $this->arguments[] = $this->valueToString($arg);
        }
    }

    /**
     * Execute validator
     * 
     * @param mixed $value attribute value to validate
     * 
     * @return bool true if attribute valid
     */
    public function run($value): bool
    {
        $response = ($this->callback)($value);
        if (is_array($response)) {
            $result = array_shift($response);
            if ($this->message === $this->defaultMessage) {
                $this->message = ArrayHelper::getValue($response, 'message', $this->message);
            }
        } else {
            $result = $response;
        }

        if (count($this->arguments)) {
            $this->message = sprintf($this->message, ...$this->arguments);
        }

        $this->isValid = $result === true;

        return $this->isValid();
    }

    /**
     * Get validation status
     * 
     * @return bool true if valid
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Returns a value indicating whether the validator is active for the given scenario and attribute.
     *
     * A validator is active if
     *
     * - the validator's `on` property is empty, or
     * - the validator's `on` property contains the specified scenario
     *
     * @param string $scenario scenario name
     * @return bool whether the validator applies to the specified scenario.
     */
    public function isActive(string $scenario): bool
    {
        return !in_array($scenario, $this->except, true) && (empty($this->on) || in_array($scenario, $this->on, true));
    }

    /**
     * Get validator name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns data needed for performing JavaScript client-side validation.
     *
     * @return ClientAttributeValidator|null the client-side validation data object.
     * Null if the validator does not support
     * client-side validation.
     * @see getClientOptions()
     * @see ActiveForm::enableClientValidation
     */
    public function clientValidateAttribute(): ?ClientAttributeValidator
    {
        if (!$this->enableClientValidation) {
            return null;
        }

        return ArrayHelper::getValue($this->options, 'clientValidator');
    }

    /**
     * Convert value to string.
     * 
     * @param mixed $value
     *
     * @return string
     */
    protected function valueToString($value)
    {
        if (null === $value) {
            return 'null';
        }

        if (true === $value) {
            return 'true';
        }

        if (false === $value) {
            return 'false';
        }

        if (is_array($value)) {
            return '[' . implode(', ', array_map([$this, 'valueToString'], $value)) . ']';
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return get_class($value) . ': ' . $this->valueToString($value->__toString());
            }

            if ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
                return get_class($value) . ': ' . $this->valueToString($value->format('c'));
            }

            return get_class($value);
        }

        if (is_resource($value)) {
            return 'resource';
        }

        if (is_string($value)) {
            return '"' . $value . '"';
        }

        return (string) $value;
    }
}