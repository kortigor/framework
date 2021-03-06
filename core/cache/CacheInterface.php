<?php

declare(strict_types=1);

namespace core\cache;

use DateInterval;
use core\cache\dependency\Dependency;
use core\cache\exception\InvalidArgumentException;
use core\cache\exception\RemoveCacheException;
use core\cache\exception\SetCacheException;
use Psr\SimpleCache\CacheInterface as PsrSimpleCacheInterface;

/**
 * CacheInterface is a wrapper over PSR-16 `\Psr\SimpleCache\CacheInterface` that allows you
 * to implement the cache stampede prevention using the "Probably early expiration" algorithm.
 * 
 * @see \Psr\SimpleCache\CacheInterface
 */
interface CacheInterface
{
    /**
     * Returns the actual handler of the cache.
     *
     * @return PsrSimpleCacheInterface The actual cache handler.
     */
    public function psr(): PsrSimpleCacheInterface;

    /**
     * The method combines retrieving and setting the value identified by the `$key`.
     *
     * It will save the result of `$callable` execution if there is no cache available for the `$key`.
     * This method allows you to implement "Probably early expiration".
     *
     * Usage example:
     *
     * ```php
     * public function getTopProducts(int $count = 10) {
     *     $key = ['top-products', $count];
     *     return $this->cache->getOrSet($key, function (\Psr\SimpleCache\CacheInterface $cache) use ($count) {
     *         return $this->getTopNProductsFromDatabase($count);
     *     }, 1000);
     * }
     * ```
     *
     * @param mixed $key The key identifying the value to be cached.
     * @param callable $callable The callable or closure that will be used to generate a value to be cached.
     *
     * @param DateInterval|int|null $ttl The TTL of this value. If not set, default value is used.
     * @param Dependency|null $dependency The dependency of the cache value. If the dependency
     * changes, the corresponding value in the cache will be invalidated when it is fetched.
     * @param float $beta The value for calculating the range that is used for "Probably early expiration".
     * The larger the value, the larger the range. The default value is 1.0, which is sufficient in most cases.
     * 
     * @return mixed Result of `$callable` execution.
     *
     * @throws InvalidArgumentException Must be thrown if the `$key` or `$ttl` is not a legal value.
     * @throws SetCacheException Must be thrown if the data could not be set in the cache.
     */
    public function getOrSet($key, callable $callable, $ttl = null, Dependency $dependency = null, float $beta = 1.0);

    /**
     * Removes a value with the specified key from cache.
     *
     * @param mixed $key The key identifying the value to be removed from cache.
     *
     * @throws InvalidArgumentException MUST be thrown if the `$key` is not a legal value.
     * @throws RemoveCacheException Must be thrown if the data could not be removed from the cache.
     */
    public function remove($key): void;
}