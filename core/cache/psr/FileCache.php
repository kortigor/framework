<?php

declare(strict_types=1);

namespace core\cache\psr;

use DateInterval;
use DateTime;
use Traversable;
use core\cache\exception\InvalidArgumentException;
use core\cache\exception\CacheFileException;
use Psr\SimpleCache\CacheInterface as PsrSimpleCacheInterface;

/**
 * FileCache implements a cache handler using files.
 *
 * For each data value being cached, FileCache will store it in a separate file.
 * FileCache will perform garbage collection automatically to remove expired cache files.
 *
 * @see \Psr\SimpleCache\CacheInterface for common cache operations that are supported by FileCache.
 * @link https://github.com/yiisoft/cache-file Forked from
 */
class FileCache implements PsrSimpleCacheInterface
{
    protected const TTL_INFINITY = 31536000; // 1 year

    protected const TTL_EXPIRED = -1;

    /**
     * @var string The cache file suffix. Defaults to '.bin'.
     */
    protected string $fileSuffix = '.bin';

    /**
     * @var int|null The permission to be set for newly created cache files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    protected ?int $fileMode = null;

    /**
     * @var int The permission to be set for newly created directories.
     * This value will be used by PHP `chmod()` function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    protected int $directoryMode = 0775;

    /**
     * @var int The level of sub-directories to store cache files. Defaults to 1.
     * If the system has huge number of cache files (e.g. one million), you may use a bigger value
     * (usually no bigger than 3). Using sub-directories is mainly to ensure the file system
     * is not over burdened with a single directory having too many files.
     */
    protected int $directoryLevel = 1;

    /**
     * @var int The probability (parts per million) that garbage collection (GC) should be performed
     * when storing a piece of data in the cache. Defaults to 10, meaning 0.001% chance.
     * This number should be between 0 and 1000000. A value 0 means no GC will be performed at all.
     */
    protected int $gcProbability = 10;

    /**
     * Constructor
     * 
     * @param string $cachePath The directory to store cache files.
     *
     * @throws CacheFileException If failed to create cache directory.
     */
    public function __construct(protected string $cachePath)
    {
        if (!$this->createDirectoryIfNotExists($cachePath)) {
            throw new CacheFileException("Failed to create cache directory '{$cachePath}'.");
        }
    }

    public function get($key, $default = null)
    {
        $this->validateKey($key);
        $file = $this->getCacheFile($key);

        if (!$this->isExistsAndNotExpired($file) || ($filePointer = @fopen($file, 'rb')) === false) {
            return $default;
        }

        flock($filePointer, LOCK_SH);
        $value = stream_get_contents($filePointer);
        flock($filePointer, LOCK_UN);
        fclose($filePointer);

        return unserialize($value);
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->validateKey($key);
        $this->gc();
        $expiration = $this->ttlToExpiration($ttl);

        if ($expiration <= self::TTL_EXPIRED) {
            return $this->delete($key);
        }

        $file = $this->getCacheFile($key);
        $cacheDirectory = dirname($file);

        if (!is_dir($this->cachePath) || ($this->directoryLevel > 0 && !$this->createDirectoryIfNotExists($cacheDirectory))) {
            throw new CacheFileException("Failed to create cache directory '{$cacheDirectory}'.");
        }

        // If ownership differs the touch call will fail, so we try to
        // rebuild the file from scratch by deleting it first
        if (function_exists('posix_geteuid') && is_file($file) && fileowner($file) !== posix_geteuid()) {
            @unlink($file);
        }

        if (file_put_contents($file, serialize($value), LOCK_EX) === false) {
            return false;
        }

        if ($this->fileMode !== null) {
            chmod($file, $this->fileMode);
        }

        return touch($file, $expiration);
    }

    public function delete($key): bool
    {
        $this->validateKey($key);
        $file = $this->getCacheFile($key);

        if (!is_file($file)) {
            return true;
        }

        return unlink($file);
    }

    public function clear(): bool
    {
        $this->removeCacheFiles($this->cachePath, false);
        return true;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $keys = $this->iterableToArray($keys);
        $this->validateKeys($keys);
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }

        return $results;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        $values = $this->iterableToArray($values);
        $this->validateKeys(array_map('strval', array_keys($values)));

        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple($keys): bool
    {
        $keys = $this->iterableToArray($keys);
        $this->validateKeys($keys);

        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has($key): bool
    {
        $this->validateKey($key);
        return $this->isExistsAndNotExpired($this->getCacheFile($key));
    }

    /**
     * @param string $fileSuffix The cache file suffix. Defaults to '.bin'.
     *
     * @return self
     */
    public function withFileSuffix(string $fileSuffix): self
    {
        $new = clone $this;
        $new->fileSuffix = $fileSuffix;
        return $new;
    }

    /**
     * @param int $fileMode The permission to be set for newly created cache files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     *
     * @return self
     */
    public function withFileMode(int $fileMode): self
    {
        $new = clone $this;
        $new->fileMode = $fileMode;
        return $new;
    }

    /**
     * @param int $directoryMode The permission to be set for newly created directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group, but read-only for other users.
     *
     * @return self
     */
    public function withDirectoryMode(int $directoryMode): self
    {
        $new = clone $this;
        $new->directoryMode = $directoryMode;
        return $new;
    }

    /**
     * @param int $directoryLevel The level of sub-directories to store cache files. Defaults to 1.
     * If the system has huge number of cache files (e.g. one million), you may use a bigger value
     * (usually no bigger than 3). Using sub-directories is mainly to ensure the file system
     * is not over burdened with a single directory having too many files.
     *
     * @return self
     */
    public function withDirectoryLevel(int $directoryLevel): self
    {
        $new = clone $this;
        $new->directoryLevel = $directoryLevel;
        return $new;
    }

    /**
     * @param int $gcProbability The probability (parts per million) that garbage collection (GC) should
     * be performed when storing a piece of data in the cache. Defaults to 10, meaning 0.001% chance.
     * This number should be between 0 and 1000000. A value 0 means no GC will be performed at all.
     *
     * @return self
     */
    public function withGcProbability(int $gcProbability): self
    {
        $new = clone $this;
        $new->gcProbability = $gcProbability;
        return $new;
    }

    /**
     * Converts TTL to expiration.
     *
     * @param DateInterval|int|null $ttl
     *
     * @return int
     */
    protected function ttlToExpiration($ttl): int
    {
        $ttl = $this->normalizeTtl($ttl);

        if ($ttl === null) {
            return self::TTL_INFINITY + time();
        }

        if ($ttl <= 0) {
            return self::TTL_EXPIRED;
        }

        return $ttl + time();
    }

    /**
     * Normalizes cache TTL handling strings and `DateInterval` objects.
     *
     * @param DateInterval|int|string|null $ttl The raw TTL.
     *
     * @return int|null TTL value as UNIX timestamp or null meaning infinity
     */
    protected function normalizeTtl($ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof DateInterval) {
            return (new DateTime('@0'))->add($ttl)->getTimestamp();
        }

        return (int) $ttl;
    }

    /**
     * Ensures that the directory is created.
     *
     * @param string $path The path to the directory.
     *
     * @return bool Whether the directory was created.
     */
    protected function createDirectoryIfNotExists(string $path): bool
    {
        return is_dir($path) || (!is_file($path) && mkdir($path, $this->directoryMode, true) && is_dir($path));
    }

    /**
     * Returns the cache file path given the cache key.
     *
     * @param string $key The cache key.
     *
     * @return string The cache file path.
     */
    protected function getCacheFile(string $key): string
    {
        if ($this->directoryLevel < 1) {
            return $this->cachePath . DIRECTORY_SEPARATOR . $key . $this->fileSuffix;
        }

        $base = rtrim($this->cachePath, DIRECTORY_SEPARATOR);

        for ($i = 0; $i < $this->directoryLevel; ++$i) {
            if (($prefix = substr($key, $i + $i, 2)) !== false) {
                $base .= DIRECTORY_SEPARATOR . $prefix;
            }
        }

        return $base . DIRECTORY_SEPARATOR . $key . $this->fileSuffix;
    }

    /**
     * Recursively removing expired cache files under a directory. This method is mainly used by {@see gc()}.
     *
     * @param string $path The directory under which expired cache files are removed.
     * @param bool $expiredOnly Whether to only remove expired cache files.
     * If false, all files under `$path` will be removed.
     */
    protected function removeCacheFiles(string $path, bool $expiredOnly): void
    {
        if (($handle = @opendir($path)) === false) {
            return;
        }

        while (($file = readdir($handle)) !== false) {
            if (strncmp($file, '.', 1) === 0) {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $file;

            if (is_dir($fullPath)) {
                $this->removeCacheFiles($fullPath, $expiredOnly);

                if (!$expiredOnly && !@rmdir($fullPath)) {
                    $errorMessage = error_get_last()['message'] ?? '';
                    throw new CacheFileException("Unable to remove directory '{$fullPath}': {$errorMessage}");
                }
            } elseif ((!$expiredOnly || @filemtime($fullPath) < time()) && !@unlink($fullPath)) {
                $errorMessage = error_get_last()['message'] ?? '';
                throw new CacheFileException("Unable to remove file '{$fullPath}': {$errorMessage}");
            }
        }

        closedir($handle);
    }

    /**
     * Removes expired cache files.
     *
     * @throws Exception
     */
    protected function gc(): void
    {
        if (random_int(0, 1000000) < $this->gcProbability) {
            $this->removeCacheFiles($this->cachePath, true);
        }
    }

    /**
     * Validate cache key
     * 
     * @param mixed $key
     */
    protected function validateKey($key): void
    {
        if (!is_string($key) || $key === '' || strpbrk($key, '{}()/\@:')) {
            throw new InvalidArgumentException('Invalid key value.');
        }
    }

    /**
     * Validate multiple cache keys
     * 
     * @param array $keys
     */
    protected function validateKeys(array $keys): void
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
    }

    /**
     * Converts iterable to array. If provided value is not iterable it throws an InvalidArgumentException.
     *
     * @param mixed $iterable
     *
     * @return array
     */
    protected function iterableToArray($iterable): array
    {
        if (!is_iterable($iterable)) {
            throw new InvalidArgumentException('Iterable is expected, got ' . gettype($iterable));
        }

        return $iterable instanceof Traversable ? iterator_to_array($iterable) : (array) $iterable;
    }

    /**
     * Whether cache file exists and not expired
     * 
     * @param string $file
     *
     * @return bool True if the file exists and not expired
     */
    protected function isExistsAndNotExpired(string $file): bool
    {
        return is_file($file) && filemtime($file) > time();
    }
}