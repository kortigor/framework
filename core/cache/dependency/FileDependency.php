<?php

declare(strict_types=1);

namespace core\cache\dependency;

use core\cache\CacheInterface;

/**
 * FileDependency represents a dependency based on a file's last modification time.
 *
 * If the last modification time of the file specified via `$fileName` is changed,
 * the dependency is considered as changed.
 */
final class FileDependency extends Dependency
{
    /**
     * @param string $fileName The file path whose last modification time is used to
     * check if the dependency has been changed.
     */
    public function __construct(private string $fileName)
    {
    }

    /**
     * @param CacheInterface $cache
     *
     * @return false|int
     */
    protected function generateDependencyData(CacheInterface $cache)
    {
        clearstatcache(false, $this->fileName);
        return @filemtime($this->fileName);
    }
}