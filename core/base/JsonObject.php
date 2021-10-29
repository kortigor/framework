<?php

declare(strict_types=1);

namespace core\base;

use stdClass;
use ArrayAccess;
use JsonSerializable;
use core\validators\Assert;

/**
 * Simple functionality to work with JSON data as object and ArrayAccess.
 */
class JsonObject implements ArrayAccess, JsonSerializable
{
    /**
     * @var stdClass Decoded json string as object.
     */
    private stdClass $container;

    /**
     * Constructor.
     * 
     * @param string $encoded Json encoded string.
     */
    public function __construct(string $encoded)
    {
        Assert::json($encoded);
        $decoded = json_decode($encoded);
        $this->container = $decoded instanceof stdClass ? $decoded : new stdClass;
    }

    public function __get(string $prop)
    {
        return property_exists($this->container, $prop) ? $this->container->$prop : null;
    }

    public function __set(string $prop, $value)
    {
        $this->container->$prop = $value;
    }

    public function __unset(string $prop)
    {
        unset($this->container->$prop);
    }

    public function __invoke(string $prop = null): stdClass
    {
        if ($prop === null) {
            return $this->container;
        }
        return $this->container->$prop;
    }

    public function toArray(): array
    {
        return json_decode(json_encode($this->container), true);
    }

    /* ArrayAccess implementation */

    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    public function offsetExists($offset)
    {
        return property_exists($this->container, $offset);
    }

    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }


    /* JsonSerializable implementation */

    public function jsonSerialize()
    {
        return $this->container;
    }
}