<?php

declare(strict_types=1);

namespace core\cache\dependency;

use core\cache\CacheInterface;

/**
 * CallbackDependency represents a dependency based on the result of a callback.
 *
 * The dependency is reported as unchanged if and only if the result of the callback is
 * the same as the one evaluated when storing the data to cache.
 */
final class CallbackDependency extends Dependency
{
    public function __construct(private callable $callback)
    {
    }

    protected function generateDependencyData(CacheInterface $cache)
    {
        return ($this->callback)($cache);
    }
}