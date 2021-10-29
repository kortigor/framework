<?php

declare(strict_types=1);

namespace core\cache\psr;

use core\cache\exception\CacheFileException;
use Psr\SimpleCache\CacheInterface as PsrSimpleCacheInterface;

/**
 * FileCacheExp implements a cache handler using files.
 * 
 * Advantages:
 * Unlike FileCache, FileCacheExp stored cached value and expiration time in the file via var_export().
 * In some cases it has better read performance by the reasons:
 *  - 'require' operator faster than sequence of `fopen()`, `stream_get_contents()`, `unserialize()`;
 *  - modification time set to the future, used for setting expiration time by FileCache, can lead to performance hit too.
 * 
 * Disadvantages:
 *  - 'Has' checking and garbage collection work slower, because to check invalidation need to require file.
 *
 * For each data value being cached, FileCacheExp will store it in a separate file.
 * FileCacheExp will perform garbage collection automatically to remove expired cache files.
 *
 * @see \Psr\SimpleCache\CacheInterface for common cache operations that are supported by FileCacheExp.
 * @author Kort <kort.igor@gmail.com>
 */
final class FileCacheExp extends FileCache implements PsrSimpleCacheInterface
{
    /**
     * @var string The cache file suffix. Defaults to '.vex'.
     */
    protected string $fileSuffix = '.vex';

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        $this->validateKey($key);
        $file = $this->getCacheFile($key);

        if (!$this->isExists($file)) {
            return $default;
        }

        list($expiration, $value) = require $file;

        if ($this->isExpired($expiration)) {
            return $default;
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
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

        $code = '<?php return array('
            . var_export($expiration, true) . ','
            . var_export($value, true) . ','
            . ');';

        if (file_put_contents($file, $code, LOCK_EX) === false) {
            return false;
        }

        if ($this->fileMode !== null) {
            chmod($file, $this->fileMode);
        }

        return touch($file);
    }

    /**
     * @inheritDoc
     */
    public function has($key): bool
    {
        $this->validateKey($key);
        $file = $this->getCacheFile($key);
        if ($this->isExistsAndNotExpired($file)) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
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
            } else {
                if ((!$expiredOnly || $this->isExistsAndNotExpired($fullPath)) && !@unlink($fullPath)) {
                    $errorMessage = error_get_last()['message'] ?? '';
                    throw new CacheFileException("Unable to remove file '{$fullPath}': {$errorMessage}");
                }
            }
        }

        closedir($handle);
    }

    /**
     * @inheritDoc
     */
    protected function isExistsAndNotExpired(string $file): bool
    {
        return is_file($file) && !$this->isExpired($this->getExpiration($file));
    }

    /**
     * Whether cache file exists
     * 
     * @param string $file
     *
     * @return bool
     */
    private function isExists(string $file): bool
    {
        return is_file($file);
    }

    /**
     * Whether cache file expired
     * 
     * @param int $time File expiration time time
     * 
     * @return bool True if the file is expired
     */
    private function isExpired(int $time): bool
    {
        return $time < time();
    }

    /**
     * Get file expiration time
     * 
     * @param string $file
     * 
     * @return int Expiration timestamp
     */
    private function getExpiration(string $file): int
    {
        list($expiration) = require $file;
        return (int) $expiration;
    }
}