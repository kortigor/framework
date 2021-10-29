<?php

declare(strict_types=1);

namespace core\collections;

use IteratorAggregate;
use Countable;
use ArrayAccess;
use ArrayIterator;
use Serializable;
use JsonSerializable;

/**
 * Simple collection implementation.
 */
class Collection implements IteratorAggregate, Countable, ArrayAccess, Serializable, JsonSerializable
{
    /**
     * Constructor.
     * 
     * @param array $data Collection array.
     */
    public function __construct(protected array $storage = [])
    {
    }

    /**
     * Sort collection using a user-defined comparison function.
     * 
     * @param callable $ufunc
     * 
     * @return bool
     * @see https://www.php.net/manual/en/function.usort.php
     */
    public function usort(callable $ufunc): bool
    {
        return usort($this->storage, $ufunc);
    }

    /**
     * Filters elements of an array using a callback function.
     * 
     * @param callable $ufunc
     * @param int $flag
     * 
     * @return void
     * @see https://www.php.net/manual/en/function.array-filter.php
     */
    public function array_filter(callable $ufunc, $flag = 0): void
    {
        $this->storage = array_filter($this->storage, $ufunc, $flag);
    }

    /**
     * Searches in collection for a given value and returns the first corresponding key if successful.
     * 
     * @param mixed $value
     * 
     * @return int|string|false
     * @see https://www.php.net/manual/en/function.array-search.php
     */
    public function array_search($value)
    {
        return array_search($value, $this->storage);
    }

    /**
     * Remove duplicates.
     * 
     * @param int $flag
     * 
     * @return void
     * @see https://www.php.net/manual/en/function.array-unique.php
     * @see https://www.php.net/manual/en/array.constants.php
     */
    public function array_unique(int $flag = SORT_STRING): void
    {
        $this->storage = array_unique($this->storage, $flag);
    }

    /**
     * Get collection as array.
     * 
     * @return array
     */
    public function toArray(): array
    {
        return $this->storage;
    }

    /**
     * Clear collection.
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->storage = [];
    }

    /* IteratorAggregate implementation */

    public function getIterator()
    {
        return new ArrayIterator($this->storage);
    }

    /* Countable implementation */

    public function count(): int
    {
        return count($this->storage);
    }

    /* ArrayAccess implementation */

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->storage[] = $value; // Place to the end.
        } else {
            $this->storage[$offset] = $value; // Assign to a specific key
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->storage[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->storage[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->storage[$offset] ?? null;
    }

    /* Serializable implementation */

    public function serialize(): string
    {
        return serialize($this->storage);
    }

    public function unserialize($data)
    {
        $this->storage = unserialize($data);
    }

    /* JsonSerializable implementation */

    public function jsonSerialize()
    {
        return $this->storage;
    }
}