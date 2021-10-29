<?php

declare(strict_types=1);

/**
 * @version 2.0.5
 */

namespace kartik\base;

use ReflectionClass;

/**
 * Global configuration helper class for Krajee extensions.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 */
class Config
{
    /**
     * @var string the default reason appended for exceptions
     */
    const DEFAULT_REASON = 'for your selected functionality';

    /**
     * Convert a language string in i18n format to a ISO-639 format (2 or 3 letter code).
     *
     * @param string $language the input language string
     *
     * @return string
     */
    public static function getLang(string $language): string
    {
        $pos = strpos($language, '-');
        return $pos > 0 ? substr($language, 0, $pos) : $language;
    }

    /**
     * Get the current directory of the extended class object
     *
     * @param object $object the called object instance
     *
     * @return string
     * @throws \ReflectionException
     */
    public static function getCurrentDir(object $object): string
    {
        if (empty($object)) {
            return '';
        }
        $child = new ReflectionClass($object);
        return dirname($child->getFileName());
    }

    /**
     * Check if a file exists
     *
     * @param string $file the file with path in URL format
     *
     * @return bool
     */
    public static function fileExists(string $file): bool
    {
        $file = str_replace('/', DS, $file);
        return file_exists($file);
    }

    /**
     * Check if HTML options has specified CSS class
     * @param array $options the HTML options
     * @param string $cssClass the css class to test
     * @return bool
     */
    public static function hasCssClass(array $options, string $cssClass): bool
    {
        if (!isset($options['class'])) {
            return false;
        }

        $classes = is_array($options['class'])
            ? $options['class']
            : preg_split('/\s+/', $options['class'], -1, PREG_SPLIT_NO_EMPTY);

        return in_array($cssClass, $classes);
    }
}
