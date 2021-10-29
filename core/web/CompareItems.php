<?php

declare(strict_types=1);

namespace core\web;

use core\validators\Assert;
use InvalidArgumentException;
use JsonException;

/**
 * Compare items model
 */
class CompareItems
{
    /**
     * @var array
     */
    protected array $items = [];

    /**
     * Constructor
     * 
     * @param array $items array list of ids selected to compare, such as:
     * ```
     * $items = ['production_id1', 'production_id2', 'production_id3'...];
     * ```
     * @throws InvalidArgumentException if compare item invalid
     */
    public function __construct(array $items)
    {
        // Assert all items is consist
        foreach ($items as $key => $id) {
            Assert::uuid($id);
        }

        $this->items = $items;
    }

    /**
     * Create instance from cookie
     * 
     * @param Cookie $cookie Compare cookie.
     * 
     * @return self
     * @throws JsonException If json data invalid
     */
    public static function fromCookie(Cookie $cookie): self
    {
        $data = $cookie->getValue();
        $items = $data ? json_decode($data, true, 512, JSON_THROW_ON_ERROR) : [];
        $items = is_array($items) ? $items : [];
        return new static($items);
    }

    /**
     * Remove compare item
     * 
     * @param string $id
     * 
     * @return bool True if the item exists in the compare and was removed successfully
     */
    public function removeItem(string $id): bool
    {
        $key = array_search($id, $this->items);
        if ($key === false) {
            return false;
        }

        unset($this->items[$key]);
        return true;
    }

    /**
     * Add item to compare
     * 
     * @param string $id
     * 
     * @return bool True if the item was not exists in the compare and was added successfully
     */
    public function addItem(string $id): bool
    {
        if (in_array($id, $this->items)) {
            return false;
        }

        $this->items[] = $id;
        return true;
    }

    /**
     * Count compare items.
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Whether compare is empty
     * 
     * @return bool True if empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function __toString()
    {
        return json_encode((object) $this->items, JSON_UNESCAPED_UNICODE);
    }
}