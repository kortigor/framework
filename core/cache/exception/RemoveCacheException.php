<?php

declare(strict_types=1);

namespace core\cache\exception;

final class RemoveCacheException extends CacheException
{
    /**
     * @param string $key
     */
    public function __construct(string $key)
    {
        parent::__construct($key, 'Failed to delete the cache.');
    }
}