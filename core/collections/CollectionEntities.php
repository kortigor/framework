<?php

declare(strict_types=1);

namespace core\collections;

use Closure;
use Exception;
use IteratorAggregate;
use Countable;
use ArrayAccess;
use Serializable;
use JsonSerializable;

/**
 * An iterable collection of entities of the same type.
 * Array keys are only integer (array not associative)
 * Supports continuity of array index when deleting elements
 */
class CollectionEntities extends Collection implements IteratorAggregate, Countable, ArrayAccess, Serializable, JsonSerializable
{
    /**
     * @var Closure
     */
    protected Closure $counterTotal;

    /**
     * @param array $data Entities array
     * @param Closure|null $totalCounter The function of calculating the total number of entities.
     * For cases when the number of passed entities does not match the total number in the system.
     * 
     * Example:
     * A selection of a part of publications (20 out of 1000) is passed and publications list is paginated.
     * The function is used to calculate the total number of publications
     */
    public function __construct(array $data = [], Closure $totalCounter = null)
    {
        if ($totalCounter !== null) {
            $this->setTotalCounter($totalCounter);
        }
        parent::__construct(array_values($data)); // Reset the keys so that the indexes follow each other
    }

    /**
     * Sets the function for calculating the total number of entities.
     * 
     * @param Closure $counter
     * 
     * @return self
     */
    public function setTotalCounter(Closure $counter): self
    {
        $this->counterTotal = $counter;
        return $this;
    }

    /**
     * Counting the total number of entities.
     * 
     * @return int Total number of entities.
     */
    public function countTotal(): int
    {
        if (!isset($this->counterTotal)) {
            return count($this->storage);
        }

        $cnt = $this->counterTotal;
        return $cnt();
    }

    /* ArrayAccess implementation */

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            // Place to the end
            $this->storage[] = $value;
        } elseif (is_int($offset)) {
            // Assign to a specific existing key or add to the end if the key is (count() + 1)
            if (isset($this->storage[$offset]) || $offset == count($this->storage) + 1) {
                $this->storage[$offset] = $value;
            } else {
                throw new Exception(sprintf('%s, offset %u breaks index continuity', static::class, $offset));
            }
        } else {
            throw new Exception(sprintf('%s, offset must be only integer type, "%s" given', static::class, $offset));
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->storage[$offset]);
        $this->storage = array_values($this->storage); // Reset the keys so that the indexes follow each other
    }
}
