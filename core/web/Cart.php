<?php

declare(strict_types=1);

namespace core\web;

use core\validators\Assert;
use InvalidArgumentException;
use JsonException;

/**
 * Cart model
 */
class Cart
{
    /**
     * @var array
     */
    protected array $items = [];

    /**
     * Constructor
     * 
     * @param array $items Associative array of cart items with following structure:
     * ```
     * $items = [
     * 'production_id1' => ['quantity' => 1, 'comment' => 'Production comment 1'],
     * 'production_id2' => ['quantity' => 3, 'comment' => 'Production comment 2'],
     * ...
     * ];
     * ```
     * @throws InvalidArgumentException if cart item invalid
     */
    public function __construct(array $items)
    {
        // Assert all items is consist
        foreach ($items as $id => $item) {
            Assert::uuid($id);
            Assert::keyExists($item, 'quantity');
            Assert::keyExists($item, 'comment');
            Assert::integer($item['quantity']);
            Assert::string($item['comment']);
        }

        $this->items = $items;
    }

    /**
     * Create instance from cookie
     * 
     * @param Cookie $cookie Cart cookie.
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
     * Remove cart item
     * 
     * @param string $id
     * 
     * @return bool True if the item exists in the cart and was removed successfully
     */
    public function removeItem(string $id): bool
    {
        if (!isset($this->items[$id])) {
            return false;
        }

        unset($this->items[$id]);
        return true;
    }

    /**
     * Add item to cart
     * 
     * @param string $id
     * 
     * @return bool True if the item was not exists in the cart and was added successfully
     */
    public function addItem(string $id): bool
    {
        if (isset($this->items[$id])) {
            return false;
        }

        $this->items[$id] = ['quantity' => 1, 'comment' => ''];
        return true;
    }

    /**
     * Get cart item with id
     * 
     * @param string $id
     * 
     * @return array|null
     */
    public function getItem(string $id): ?array
    {
        if (!isset($this->items[$id])) {
            return null;
        }

        $item = $this->items[$id];
        $item['id'] = $id;
        return $item;
    }

    /**
     * Get array of item ids placed in the cart
     * 
     * @return array
     */
    public function getIds(): array
    {
        return array_keys($this->items);
    }

    /**
     * Count cart items.
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Whether cart is empty
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