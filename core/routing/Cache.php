<?php

declare(strict_types=1);

namespace core\routing;

use core\cache\exception\CacheFileException;
use Psr\SimpleCache\CacheInterface;

/**
 * Simple cache implementation. Only for routing component usage.
 * 
 * All data stores in the files.
 */
class Cache implements CacheInterface
{
    /**
     * @var string Cache directory path
     */
    private string $dir;

    /**
     * Constructor.
     * 
     * @param string $dir Cache directory path
     * @param string $id Cache id
     */
    public function __construct(string $dir, private string $id)
    {
        $this->dir = normalizePath($dir);
        if (!is_dir($this->dir) || !is_writable($this->dir)) {
            throw new CacheFileException("Cache directory '{$this->dir}' does not exists or have not enough rights to write.");
        }
    }

    public function get($key, $default = null)
    {
        $file = $this->getFile($key);
        if (is_file($file)) {
            return require $file;
        }

        return $default;
    }

    public function set($key, $value, $ttl = null)
    {
        $file = $this->getFile($key);
        $code = '<?php return ' . var_export($value, true) . ';';
        Utils::writeFile($file, $code);
    }

    public function delete($key)
    {
        if (!$this->has($key)) {
            return false;
        }

        return unlink($this->getFile($key));
    }

    public function clear()
    {
        if (($handle = @opendir($this->dir)) === false) {
            return false;
        }

        while (($file = readdir($handle)) !== false) {
            if (strncmp($file, '.', 1) === 0) {
                continue;
            }

            $path = $this->dir . DS . $file;
            if (is_file($path)) {
                if (!@unlink($path)) {
                    $errorMessage = error_get_last()['message'] ?? '';
                    throw new CacheFileException("Unable to remove file '{$path}': {$errorMessage}");
                }
            }
        }

        closedir($handle);
    }

    public function getMultiple($keys, $default = null)
    {
        foreach ($keys as $key) {
            yield $this->get($key);
        }
    }

    public function setMultiple($values, $ttl = null)
    {
        return true;
    }

    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has($key)
    {
        return is_file($this->getFile($key));
    }

    private function getKey(string $key): string
    {
        return $key . '_' . $this->id;
    }

    private function getFile(string $key): string
    {
        return $this->dir . DS . $this->getKey($key) . '.php';
    }
}