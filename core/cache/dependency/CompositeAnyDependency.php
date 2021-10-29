<?php

declare(strict_types=1);

namespace core\cache\dependency;

use core\cache\CacheInterface;
use core\cache\Exception\InvalidArgumentException;

/**
 * CompositeAnyDependency represents a dependency which is composed of a list of other dependencies.
 *
 * The dependency is reported as changed if any sub-dependency is changed.
 */
final class CompositeAnyDependency extends Dependency
{
    /**
     * Constructor.
     * 
     * @param Dependency[] $dependencies List of dependencies that this dependency is composed of.
     * Each array element must be a dependency object.
     */
    public function __construct(private array $dependencies = [])
    {
        foreach ($dependencies as $dependency) {
            if (!($dependency instanceof Dependency)) {
                throw new InvalidArgumentException(sprintf(
                    'The dependency must be a "%s" instance, "%s" received',
                    Dependency::class,
                    is_object($dependency) ? get_class($dependency) : gettype($dependency),
                ));
            }
        }
    }

    public function evaluateDependency(CacheInterface $cache): void
    {
        foreach ($this->dependencies as $dependency) {
            $dependency->evaluateDependency($cache);
        }
    }

    /**
     * @param CacheInterface $cache
     *
     * @return mixed
     */
    protected function generateDependencyData(CacheInterface $cache)
    {
        return null;
    }

    public function isChanged(CacheInterface $cache): bool
    {
        foreach ($this->dependencies as $dependency) {
            if ($dependency->isChanged($cache)) {
                return true;
            }
        }

        return false;
    }
}