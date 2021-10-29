<?php

declare(strict_types=1);

namespace core\collections;

use core\web\SessionStorage;

/**
 * Flash messages simple collection
 */
class FlashCollection
{
    /**
     * @var string
     */
    const REMOVE_AFTER_ACCESS = '__removeAfterAccess';

    /**
     * Constructor
     * 
     * @param string $storage Flashes session storage
     */
    public function __construct(protected SessionStorage $storage)
    {
    }

    /**
     * Returns a flash message.
     * If the collection variable does not exist, the `$defaultValue` will be returned.
     * 
     * @param string $key the key identifying the flash message
     * @param mixed $defaultValue value to be returned if the flash message does not exist.
     * @return mixed the flash message or an array of messages if add() was used or `$defaultValue`
     */
    public function get(string $key, $defaultValue = null)
    {
        return $this->isNeedRemove($key)
            ? $this->shift($key, $defaultValue)
            : $this->storage->read($key, $defaultValue);
    }

    /**
     * Returns and deletes a flash message.
     * If the collection variable does not exist, the `$defaultValue` will be returned.
     * 
     * @param string $key the key identifying the flash message
     * @param mixed $defaultValue value to be returned if the flash message does not exist.
     * @return mixed the flash message or an array of messages if add() was used or `$defaultValue`
     */
    public function shift(string $key, $defaultValue = null)
    {
        $removes = $this->storage->read(static::REMOVE_AFTER_ACCESS);
        unset($removes[$key]);
        $this->storage->write(static::REMOVE_AFTER_ACCESS, $removes);

        return $this->storage->shift($key, $defaultValue);
    }

    /**
     * Returns all flash messages.
     *
     * You may use this method to display all the flash messages in a view file:
     *
     * ```php
     * foreach ($collection->getAll() as $key => $message) {
     *     echo '<div class="alert alert-' . $key . '">' . $message . '</div>';
     * } 
     * ```
     *
     * With the above code you can use the [bootstrap alert][] classes such as `success`, `info`, `danger`
     * as the flash message key to influence the color of the div.
     *
     * Note that if you use `add()`, `$message` will be an array, and you will have to adjust the above code.
     *
     * [bootstrap alert]: https://getbootstrap.com/docs/4.4/components/alerts/
     *
     * @param bool $delete whether to delete the flash messages right after this method is called.
     * 
     * @return array flash messages (key => [message1, message2]).
     */
    public function getAll(bool $delete = false): array
    {
        $flashes = $this->storage->read() ?? [];

        if ($delete) {
            $this->removeAll();
        } else {
            $removes = $flashes[static::REMOVE_AFTER_ACCESS] ?? [];
            foreach (array_keys($removes) as $key) {
                $this->remove($key);
            }
        }

        unset($flashes[static::REMOVE_AFTER_ACCESS]);

        return $flashes;
    }

    /**
     * Sets a flash message.
     * 
     * If there is already an existing flash message with the same key, it will be overwritten by the new one.
     * 
     * @param string $key the key identifying the flash message.
     * @param string $value flash message
     * @param bool $removeAfterAccess whether the flash message should be automatically removed only if
     * it is accessed. If false, the flash message will be automatically removed after the next request,
     * regardless if it is accessed or not. If true (default value), the flash message will remain until after
     * it is accessed.
     */
    public function set(string $key, string $value, bool $removeAfterAccess = true): void
    {
        $this->write($key, $value, $removeAfterAccess);
    }

    /**
     * Adds a flash message.
     * 
     * If there are existing flash messages with the same key, the new one will be appended to the existing message array.
     * 
     * @param string $key the key identifying the flash message.
     * @param mixed $value flash message
     * @param bool $removeAfterAccess whether the flash message should be automatically removed only if
     * it is accessed. If false, the flash message will be automatically removed after the next request,
     * regardless if it is accessed or not. If true (default value), the flash message will remain until after
     * it is accessed.
     */
    public function add(string $key, string $value, bool $removeAfterAccess = true): void
    {
        $tmp = $value;
        if ($this->has($key)) {
            $values = (array) $this->storage->read($key);
            $values[] = $value;
            $tmp = $values;
        }

        $this->write($key, $tmp, $removeAfterAccess);
    }

    /**
     * Removes a flash message.
     * 
     * @param string $key the key identifying the flash message.
     */
    public function remove(string $key): void
    {
        $this->shift($key);
    }

    /**
     * Removes all flash messages.
     */
    public function removeAll(): void
    {
        $this->storage->clear();
    }

    /**
     * Returns a value indicating whether there are flash messages associated with the specified key.
     * 
     * @param string $key key identifying the flash message type
     * @return bool whether any flash messages exist under specified key
     */
    public function has(string $key): bool
    {
        return $this->storage->has($key);
    }

    /**
     * Write a flash message into session storage.
     * 
     * @param string $key the key identifying the flash message.
     * @param string|array $value flash message or array of flash messages if `add()` method used
     * @param bool $removeAfterAccess whether the flash message should be automatically removed only if
     * it is accessed. If false, the flash message will be automatically removed after the next request,
     * regardless if it is accessed or not. If true (default value), the flash message will remain until after
     * it is accessed.
     */
    protected function write(string $key, string|array $value, bool $removeAfterAccess = true): void
    {
        $this->storage->write($key, $value);
        if ($removeAfterAccess) {
            $removes = $this->storage->read(static::REMOVE_AFTER_ACCESS);
            $removes[$key] = true;
            $this->storage->write(static::REMOVE_AFTER_ACCESS, $removes);
        }
    }

    /**
     * Whether flash message need to remove after access
     * 
     * @param string $key Key to check
     * 
     * @return bool True if need to remove after access
     */
    protected function isNeedRemove(string $key): bool
    {
        $removes = $this->storage->read(static::REMOVE_AFTER_ACCESS);
        return isset($removes[$key]);
    }
}