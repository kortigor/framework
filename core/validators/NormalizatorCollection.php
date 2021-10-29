<?php

declare(strict_types=1);

namespace core\validators;

use BadFunctionCallException;
use RuntimeException;
use LogicException;
use core\collections\CollectionEntities;

/**
 * Represents functionality of model normalizators collection
 */
final class NormalizatorCollection extends CollectionEntities
{
    /**
     * @var int By model's instance method
     */
    const TYPE_MODEL_INSTANCE_METHOD = 1;

    /**
     * @var int By some class instance method
     */
    const TYPE_CLASS_INSTANCE_METHOD = 2;

    /**
     * @var int By some class static method
     */
    const TYPE_CLASS_STATIC_METHOD = 3;

    /**
     * @var int By global function
     */
    const TYPE_GLOBAL_FUNCTION = 4;

    /**
     * Constructor.
     * 
     * @param object $model Object (model) to normalize.
     */
    public function __construct(private object $model)
    {
        parent::__construct();
    }

    /**
     * Create collection from set of normalizarots.
     * 
     * @param object $model Object to normalize
     * @param array $normalizators Set of normalizators
     * 
     * @return self
     */
    public static function createFromArray(object $model, array $normalizators): self
    {
        $collection = new self($model);
        foreach ($normalizators as $record) {
            if (is_array($record)) {
                $attribute = array_shift($record);
                $normalizator = array_shift($record);
                if (is_array($attribute)) {
                    foreach ($attribute as $attr) {
                        $collection->add($attr, $normalizator);
                    }
                } else {
                    $collection->add($attribute, $normalizator);
                }
            }
        }

        return $collection;
    }

    /**
     * Add normalizator
     * 
     * @param string $attribute Model attribute name to normalize
     * @param string $normalizator Normalization method. It means:
     * - 1: model's instance method
     * - 2: specified class static method;
     * - 3: specified class instance method, constructor MUST require no arguments;
     * - 4: any valid PHP callback with the following signature:
     * ```php
     * function foo($value) {
     *     // compute $newValue here
     *     return $newValue;
     * }
     * // Many PHP functions qualify this signature (e.g. `trim()`).
     * ```
     * 
     * @throws RuntimeException If normalization class not exists.
     * @throws BadFunctionCallException If normalization global function not exists.
     * @throws LogicException If normalizator type not determined correctly.
     */
    public function add(string $attribute, string $normalizator): void
    {
        $callable = $this->compileCallback($normalizator);
        $closure = fn ($value) => call_user_func($callable, $value);
        $this->storage[] = new AttributeNormalizator($attribute, $closure);
    }

    /**
     * Compile normalizator callable
     * 
     * @param string $normalizator
     * 
     * @return callable
     * @throws RuntimeException If normalization class not exists.
     * @throws BadFunctionCallException If normalization global function not exists.
     * @throws LogicException If normalizator type not determined correctly.
     */
    private function compileCallback(string $normalizator): callable
    {
        switch ($this->determineType($normalizator)) {

            case self::TYPE_MODEL_INSTANCE_METHOD:
                $callable = [$this->model, $normalizator];
                if (!is_callable($callable)) {
                    $class = get_class($this->model);
                    throw new RuntimeException("Unable to call normalization method {$normalizator}() of model {$class}");
                }

                return $callable;
                // no break

            case self::TYPE_CLASS_STATIC_METHOD:
                list($class, $method) = explode('::', $normalizator, 2);
                if (!class_exists($class)) {
                    throw new RuntimeException("Normalization class {$class} does not exists");
                }
                $callable = [$class, $method];
                if (!is_callable($callable)) {
                    throw new RuntimeException("Unable to call normalization static method {$method}() of class {$class}");
                }

                return $callable;
                // no break

            case self::TYPE_CLASS_INSTANCE_METHOD;
                list($class, $method) = explode('->', $normalizator, 2);
                if (!class_exists($class)) {
                    throw new RuntimeException("Normalization class {$class} does not exists");
                }
                $callable = [new $class, $method];
                if (!is_callable($callable)) {
                    throw new RuntimeException("Unable to call normalization method {$method}() of class instance {$class}");
                }

                return $callable;
                // no break

            case self::TYPE_GLOBAL_FUNCTION:
                if (!function_exists($normalizator)) {
                    throw new BadFunctionCallException("Normalizator global function {$normalizator}() does not exists");
                }

                return $normalizator;
                // no break

            default:
                throw new LogicException("Normalizator callback type not determined");
        }
    }

    /**
     * Determine normalizator type.
     * 
     * @param string $normalizator
     * 
     * @return int Determined normalizator type.
     * @see TYPE_* class constants
     */
    private function determineType(string $normalizator): int
    {
        // by model's instance method
        if (method_exists($this->model, $normalizator)) {
            return self::TYPE_MODEL_INSTANCE_METHOD;
        }

        // by some class static method
        if (strstr($normalizator, '::')) {
            return self::TYPE_CLASS_STATIC_METHOD;
        }

        // by some class instance method
        if (strstr($normalizator, '->')) {
            return self::TYPE_CLASS_INSTANCE_METHOD;
        }

        // by global function
        return self::TYPE_GLOBAL_FUNCTION;
    }
}