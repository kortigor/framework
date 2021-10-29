<?php

declare(strict_types=1);

namespace core\cache;

use DateInterval;
use DateTime;
use core\cache\dependency\Dependency;
use core\cache\exception\InvalidArgumentException;
use core\cache\exception\RemoveCacheException;
use core\cache\exception\SetCacheException;
use core\cache\metadata\CacheItem;
use core\cache\metadata\CacheItems;
use Psr\SimpleCache\CacheInterface as PsrSimpleCacheInterface;

/**
 * Cache provides support for the data caching, including cache key composition and dependencies,
 * and uses "Probably early expiration" for cache stampede prevention.
 * The actual data caching is performed via PSR-16 instance passed to constructor.
 * PSR-16 methods possible to use via `psr()`.
 *
 * @see CacheInterface
 * @see \Psr\SimpleCache\CacheInterface
 * 
 * @link https://github.com/yiisoft/cache Forked from
 */
final class Cache implements CacheInterface
{
    /**
     * @var DependencyAwareCache Decorator over the actual cache handler.
     */
    private DependencyAwareCache $psr;

    /**
     * @var CacheItems The items that store the metadata of each cache.
     */
    private CacheItems $items;

    /**
     * @var CacheKeyNormalizer Normalizes the cache key into a valid string.
     */
    private CacheKeyNormalizer $keyNormalizer;

    /**
     * @var int|null The default TTL for a cache entry. null meaning infinity, negative or zero results in the
     * cache key deletion. This value is used by `getOrSet()`, if the duration is not explicitly given.
     */
    private ?int $defaultTtl;

    /**
     * Constructor.
     * 
     * @param PsrSimpleCacheInterface $handler The actual cache handler.
     * @param DateInterval|int|null $defaultTtl The default TTL for a cache entry.
     * null meaning infinity, negative or zero results in the cache key deletion.
     * This value is used by `getOrSet()`, if the duration is not explicitly given.
     */
    public function __construct(PsrSimpleCacheInterface $handler, $defaultTtl = null)
    {
        $this->psr = new DependencyAwareCache($this, $handler);
        $this->items = new CacheItems;
        $this->keyNormalizer = new CacheKeyNormalizer;
        $this->defaultTtl = $this->normalizeTtl($defaultTtl);
    }

    /**
     * @inheritDoc
     */
    public function psr(): PsrSimpleCacheInterface
    {
        return $this->psr;
    }

    /**
     * @inheritDoc
     */
    public function getOrSet($key, callable $callable, $ttl = null, Dependency $dependency = null, float $beta = 1.0)
    {
        $key = $this->keyNormalizer->normalize($key);
        $value = $this->getValue($key, $beta);

        return $value ?? $this->setAndGet($key, $callable, $ttl, $dependency);
    }

    /**
     * @inheritDoc
     */
    public function remove($key): void
    {
        $key = $this->keyNormalizer->normalize($key);

        if (!$this->psr->delete($key)) {
            throw new RemoveCacheException($key);
        }

        $this->items->remove($key);
    }

    /**
     * Gets the cache value.
     *
     * @param string $key The unique key of this item in the cache.
     * @param float $beta The value for calculating the range that is used for "Probably early expiration" algorithm.
     *
     * @return mixed|null The cache value or `null` if the cache is outdated or a dependency has been changed.
     */
    private function getValue(string $key, float $beta)
    {
        if ($this->items->expired($key, $beta, $this)) {
            return null;
        }

        $value = $this->psr->getRaw($key);

        if (is_array($value) && isset($value[1]) && $value[1] instanceof CacheItem) {
            [$value, $item] = $value;

            if ($item->key() !== $key || $item->expired($beta, $this)) {
                return null;
            }

            $this->items->set($item);
        }

        return $value;
    }

    /**
     * Sets the cache value and metadata, and returns the cache value.
     *
     * @param string $key The unique key of this item in the cache.
     * @param callable $callable The callable or closure that will be used to generate a value to be cached.
     *
     * @param DateInterval|int|null $ttl The TTL of this value. If not set, default value is used.
     * @param Dependency|null $dependency The dependency of the cache value.
     *
     * @return mixed|null The cache value.
     * 
     * @throws InvalidArgumentException Must be thrown if the `$key` or `$ttl` is not a legal value.
     * @throws SetCacheException Must be thrown if the data could not be set in the cache.
     */
    private function setAndGet(string $key, callable $callable, $ttl, ?Dependency $dependency)
    {
        $ttl = $this->normalizeTtl($ttl);
        $ttl ??= $this->defaultTtl;
        $value = $callable($this->psr);

        if ($dependency !== null) {
            $dependency->evaluateDependency($this);
        }

        $item = new CacheItem($key, $ttl, $dependency);

        if (!$this->psr->set($key, [$value, $item], $ttl)) {
            throw new SetCacheException($key, $value, $item);
        }

        $this->items->set($item);
        return $value;
    }

    /**
     * Normalizes cache TTL handling `null` value and `DateInterval` objects.
     *
     * @param mixed $ttl raw TTL.
     *
     * @return int|null TTL value as UNIX timestamp or null meaning infinity.
     * 
     * @throws InvalidArgumentException For invalid TTL.
     */
    private function normalizeTtl($ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof DateInterval) {
            return (new DateTime('@0'))->add($ttl)->getTimestamp();
        }

        if (is_int($ttl)) {
            return $ttl;
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid TTL "%s" specified. It must be a \DateInterval instance, an integer, or null.',
            gettype($ttl),
        ));
    }
}