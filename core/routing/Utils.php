<?php

declare(strict_types=1);

namespace core\routing;

class Utils
{
    public static function writeFile(string $file, string $content)
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            if (mkdir($dir, 0777, true) === false) {
                throw new InvalidRoutingException(sprintf('Unable to create the %s directory', $dir));
            }
        } elseif (!is_writable($dir)) {
            throw new InvalidRoutingException(sprintf('Unable to write in the %s directory', $dir));
        }

        $tmpFile = tempnam($dir, basename($file));

        if (file_put_contents($tmpFile, $content) && rename($tmpFile, $file) !== false) {
            chmod($file, 0666 & ~umask());
        } else {
            throw new InvalidRoutingException(sprintf('Failed to write cache file "%s".', $file));
        }
    }
}