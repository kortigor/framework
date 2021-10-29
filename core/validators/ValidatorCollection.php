<?php

declare(strict_types=1);

namespace core\validators;

use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Closure;
use core\interfaces\ModelValidableInterface;
use core\collections\CollectionEntities;
use core\helpers\ArrayHelper;

/**
 * Model validators collection
 */
final class ValidatorCollection extends CollectionEntities
{
    /**
     * @var int By model's instance method
     */
    const TYPE_MODEL_INSTANCE_METHOD = 1;

    /**
     * @var int By Assert class static method
     */
    const TYPE_ASSERT_STATIC_METHOD = 2;

    /**
     * Constructor.
     * 
     * @param ModelValidableInterface $model Model to validate.
     */
    public function __construct(private ModelValidableInterface $model)
    {
        parent::__construct();
    }

    /**
     * Create collection from rule set.
     * 
     * @param ModelValidableInterface $model Model to validate.
     * @param array $rules Set of validation rules.
     * 
     * @return self
     */
    public static function createFromArray(ModelValidableInterface $model, array $rules): self
    {
        $collection = new self($model);
        foreach ($rules as $name => $record) {
            if ($record instanceof AttributeValidator) {
                $validators[] = $record;
            } elseif (!is_array($record) && count($record) < 2) {
                continue;
            }

            $attribute = array_shift($record);
            $validator = ArrayHelper::shiftFirst($record);
            $options = $record;

            if (is_string($name)) {
                $options['name'] = $name;
            } else {
                $vKey = array_key_first($validator);
                $options['name'] = is_int($vKey) ? $validator[$vKey] : $vKey;
            }

            if (is_array($attribute)) {
                foreach ($attribute as $attr) {
                    $collection->add($attr, $validator, $options);
                }
            } else {
                $collection->add($attribute, $validator, $options);
            }
        }

        return $collection;
    }

    /**
     * Add validator
     * 
     * @param string $attribute Model attribute name to validate
     * @param array $validator Validation method and arguments (optional). It means:
     * - firstly, model's instance method
     * - secondary `Assert` class static method
     * @param array $options validator method additional arguments
     * @return void
     * 
     * @throws RuntimeException If normalization method not exists.
     * @throws LogicException If validator type not determined correctly.
     * @see \core\validators\Assert
     */
    public function add(string $attribute, array $validator, array $options = []): void
    {
        if (ArrayHelper::isAssociative($validator)) {
            $method = array_key_first($validator);
            $args = (array) $validator[$method];
        } else {
            $method = array_shift($validator);
            $args = [];
        }

        $options['name'] ??= $method;
        $argsCompiled = [];
        foreach ($args as $arg) {
            $argsCompiled[] = &$this->compileArgument($arg);
        }

        $options['arguments'] = $argsCompiled;
        $closure = $this->compileCallback($method, $argsCompiled);

        $clientRecord = ArrayHelper::remove($options, 'clientRule');
        if ($clientRecord) {
            if (!isset($clientRecord['message']) && isset($options['message'])) {
                $clientRecord['message'] = $options['message'];
            }

            $options['clientValidator'] = $this->getClientValidator($attribute, $clientRecord);
        }

        $this->storage[] = new AttributeValidator($attribute, $closure, $options);
    }

    /**
     * Compile validators callback
     * 
     * @param string $validator
     * @param array $arguments
     * 
     * @return Closure
     * @throws RuntimeException If normalization method not exists.
     * @throws LogicException If validator type not determined correctly.
     */
    private function compileCallback(string $validator, array $arguments): Closure
    {
        switch ($this->determineType($validator)) {

            case self::TYPE_MODEL_INSTANCE_METHOD:
                $callable = [$this->model, $validator];
                if (!is_callable($callable)) {
                    $class = get_class($this->model);
                    throw new RuntimeException("Unable to call validation method {$validator}() of model {$class}");
                }
                return fn ($value) => call_user_func($callable, $value, ...$arguments);
                // no break

            case self::TYPE_ASSERT_STATIC_METHOD:
                if ($this->checkAssertExists($validator)) {
                    $callable = [Assert::class, $validator];
                    return function ($value) use ($callable, $arguments) {
                        try {
                            call_user_func($callable, $value, ...$arguments);
                            return true;
                        } catch (InvalidArgumentException $e) {
                            return [false, 'message' => $e->getMessage()];
                        }
                    };
                }
                throw new RuntimeException("Validator Assert::{$validator}() does not exists");
                // no break

            default:
                throw new LogicException("Validator callback type not determined");
        }
    }

    /**
     * Determine validator type.
     * 
     * @param string $validator
     * 
     * @return int Determined validator type.
     * @see TYPE_* class constants
     */
    private function determineType(string $validator): int
    {
        // by model's instance method
        if (method_exists($this->model, $validator)) {
            return self::TYPE_MODEL_INSTANCE_METHOD;
        }

        // by Assert class static method
        return self::TYPE_ASSERT_STATIC_METHOD;
    }

    /**
     * Whether Assert class method exists.
     * 
     * @param string $validator
     * 
     * @return bool True if exists
     */
    private function checkAssertExists(string $validator): bool
    {
        if (method_exists(Assert::class, $validator)) {
            return true;
        }

        if ('nullOr' === substr($validator, 0, 6)) {
            return method_exists(Assert::class, str_replace('nullOr', '', $validator));
        }

        if ('all' === substr($validator, 0, 3)) {
            return method_exists(Assert::class, str_replace('all', '', $validator));
        }

        if ('emptyOr' === substr($validator, 0, 7)) {
            return method_exists(Assert::class, str_replace('emptyOr', '', $validator));
        }

        return false;
    }

    /**
     * Compile validator's argument
     * 
     * @param mixed $arg
     * 
     * @return mixed
     */
    private function &compileArgument($arg)
    {
        if (is_string($arg) && preg_match('/^{.+}$/iu', $arg)) {
            $arg = trim($arg, '{}');
            if (!property_exists($this->model, $arg)) {
                throw new InvalidArgumentException(
                    sprintf('Unable to compile validator argument. Unknown model attribute "%s"', $arg)
                );
            }
            return $this->model->$arg;
        }
        return $arg;
    }

    /**
     * @param array $record
     * 
     * @return ClientAttributeValidator
     */
    private function getClientValidator(string $attribute, array $record): ClientAttributeValidator
    {
        $validator = ArrayHelper::shiftFirst($record);
        return new ClientAttributeValidator($attribute, $validator, $record);
    }
}