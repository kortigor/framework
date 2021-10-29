<?php

declare(strict_types=1);

namespace core\cache\dependency;

use core\cache\CacheInterface;

/**
 * ValueDependency represents a dependency based on the specified value in the constructor.
 *
 * The dependency is reported as unchanged if and only if the specified value is
 * the same as the one evaluated when storing the data to cache.
 */
final class ValueDependency extends Dependency
{
    /**
     * @param mixed $value
     */
    public function __construct(private mixed $value)
    {
    }

    protected function generateDependencyData(CacheInterface $cache)
    {
        return $this->value;
    }
}