<?php

declare(strict_types=1);

namespace core\cache\exception;

use core\cache\metadata\CacheItem;

final class SetCacheException extends CacheException
{
    /**
     * @param string $key
     * @param mixed $value
     * @param CacheItem $item
     */
    public function __construct(string $key, private $value, private CacheItem $item)
    {
        parent::__construct($key, 'Failed to store the value in the cache.');
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getItem(): CacheItem
    {
        return $this->item;
    }
}