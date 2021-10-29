<?php

declare(strict_types=1);

namespace core\helpers;

use InvalidArgumentException;
use core\http\Uri;
use core\routing\UrlGenerator;
use Psr\Http\Message\UriInterface;

/**
 * Helper class to work with URLs
 */
final class Url
{
    /**
     * @var UrlGenerator Used to creating URLs
     */
    public static UrlGenerator $generator;

    /**
     * @var UriInterface Current uri object.
     */
    public static UriInterface $uri;

    /**
     * @var string Application home url.
     */
    public static string $home;

    /**
     * Generate relative URL with absolute path, based on routing rules
     * 
     * @param array $options must match following:
     * 
     * - first no associative element (required):
     * routing rule name or controller/action names directly for the rules named
     * 'controller-action' or 'controller'.
     * - second no associative element (optional):
     * controller/action names directly for 'controller-action' rule.
     * - other associative elements: url parameters in pairs name=>value
     * 
     * Reserved options:
     * - '@home' option to override static::$home value
     * 
     * Required parameters will be placed according url pattern,
     * and optional as query parameters.
     * 
     * Examples:
     * 
     * ```
     * Url::to(['blog', 'read/post', 'id'=>25]);
     * // create url from rule named 'blog', with 'read' controller and 'post' action
     * // result: '/read/post/?id=25'
     * ```
     * 
     * @param bool $absolute generate absolute URL if true.
     * 
     * @return string
     */
    public static function to(array $options = [], bool $absolute = false): string
    {
        if (empty($options)) {
            return '';
        }

        $opt1 = ArrayHelper::remove($options, 0, '');
        $opt2 = ArrayHelper::remove($options, 1, '');
        $base = ArrayHelper::remove($options, '@home', static::$home);

        // Generate by given rule name
        if (strrchr($opt1, '/') === false) {
            $ruleName = $opt1;
            if (strrchr($opt2, '/') !== false) {
                list($controller, $action) = explode('/', $opt2, 2);
            }
        }
        // Generate by given controller/action without rule name
        else {
            list($controller, $action) = explode('/', $opt1, 2);
            if ($controller && $action) {
                $ruleName = 'controller-action';
            } elseif ($controller && !$action) {
                $ruleName = 'controller';
            } elseif (!$controller) {
                $ruleName = 'site-root';
            }
        }

        $controller ??= '';
        $action ??= '';

        if ($controller) {
            $options['controller'] = $controller;
        }

        if ($action) {
            $options['action'] = $action;
        }

        $generator = static::$generator->setBase($base);
        $url = $absolute
            ? $generator->createAbsolute($ruleName, $options, $absolute)
            : $generator->create($ruleName, $options, $absolute);

        return $url;
    }

    /**
     * Get current relative url. Without sheme and host.
     * 
     * i.e. '/module/action/option/?par1=v1&par2=v2'
     * 
     * @return string
     */
    public static function current(): string
    {
        return Uri::composeComponents(
            '',
            '',
            static::$uri->getPath(),
            static::$uri->getQuery(),
            static::$uri->getFragment(),
        );
    }

    /**
     * Get relative URL like (http://example.com)'/path/to/script.php?name=value'
     * 
     * @param UriInterface $uri
     * 
     * @return string
     */
    public static function getRelative(UriInterface $uri): string
    {
        return (string) $uri->withScheme('')->withHost('')->withUserInfo('')->withPort(null);
    }

    /**
     * Returns a value indicating whether a URL is relative.
     * A relative URL does not have host info part.
     * 
     * @param string $url the URL to be checked
     * @return bool whether the URL is relative
     */
    public static function isRelative(string $url): bool
    {
        return strncmp($url, '//', 2) && strpos($url, '://') === false;
    }

    /**
     * Returns a value indicating whether a URL is absolute.
     * Absolute URL have host info part.
     * 
     * @param string $url the URL to be checked
     * @return bool whether the URL is absolute
     */
    public static function isAbsolute(string $url): bool
    {
        return !static::isRelative($url);
    }

    /**
     * Get current url query value
     * 
     * @param string $key Paremeter name
     * 
     * @return string|array|null Parameter value or null if paremeter does not exists in query
     */
    public static function getQueryValue(string $key): string|array|null
    {
        parse_str(static::$uri->getQuery(), $query);
        return $query[$key] ?? null;
    }

    /**
     * Returns a value indicating whether a URL has given query parameter.
     * 
     * @param UriInterface $uri
     * @param string $key
     * 
     * @return bool
     */
    public static function hasQueryValue(UriInterface $uri, string $key): bool
    {
        $parsed = [];
        parse_str($uri->getQuery(), $parsed);
        return isset($parsed[$key]);
    }

    /**
     * Returns the path info of the currently requested URL.
     * A path info refers to the part that is after the entry script and before the question mark (query string).
     * 
     * @param UriInterface $uri
     * @param string $base Starting part if site root URL not on domain root address.
     * In case: http://example.com/admin/ need to pass 'admin' or 'admin/' or '/admin/'
     * 
     * @return string part of the request URL that is after the entry script and before the question mark.
     * Note, the returned path info is already URL-decoded.
     */
    public static function getRealRootPath(UriInterface $uri, string $base = ''): string
    {
        $path = $uri->getPath() ?: '/';
        $base = trim($base, '/');
        // Filter out empty values and reindex array
        $split = array_values(array_filter(explode('/', $path)));
        if ($base && $split && $split[0] === $base) {
            unset($split[0]);
        }

        $result = '/' . implode('/', $split) . '/';

        return $result;
    }

    /**
     * Returns http host with sheme, like 'http(s)://examlpe.com'
     * 
     * @param UriInterface $uri
     * 
     * @return string
     * @throws InvalidArgumentException If passed $uri not contains scheme or host.
     */
    public static function getShemeHost(UriInterface $uri): string
    {
        if (!$uri->getScheme() || !$uri->getHost()) {
            throw new InvalidArgumentException('Uri does not contain sheme or host.');
        }

        return (string) $uri->withPath('')->withQuery('')->withFragment('');
    }

    /**
     * Normalize path by replace more than one slash, like '//' , '///...' with single '/'
     * 
     * @param string $path Path to normalize.
     * 
     * @return string Normalized path.
     */
    public static function normalizePath(string $path): string
    {
        return preg_replace('#/{2,}#', '/', $path);
    }

    /**
     * Normalize internal site links by strip scheme, host and port.
     * External links no processed.
     * 
     * Your site 'example.com'
     * Url 'https://example.com/path/to' => '/path/to'
     * Url 'https://youtube.com/path/to' => 'https://youtube.com/path/to'
     * 
     * @param string $value
     * 
     * @return string Internal url without scheme, host and port. External url without changes.
     */
    public static function normalizeInternalUrl(string $value): string
    {
        $uri = new Uri($value);
        $host = $uri->getHost();
        if (!$host || $host !== static::$uri->getHost()) {
            return $value;
        }

        return Url::getRelative($uri);
    }

    /**
     * Retrieve ID from Url path looks like: '/module/ID-blah-blah/' or '/module/action/ID-blah-blah/'.
     * Parts delimiter is '/' symbol.
     * 
     * @param int $ind index number of necessary part of Url path, starting from 1
     * 
     * @return int|null ID value or null if not found.
     */
    public static function getSeoId(int $ind = 2): ?int
    {
        $uri = self::getPathArray();
        if (!isset($uri[$ind])) {
            return null;
        }

        if (preg_match('/^([0-9]+)-?[0-9a-z-_]*?$/iu', $uri[$ind], $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Retrieve additional options. Placed after first two parts of Url path.
     * 
     * Example: From path '/part1/part2/part3/part4/' it returns array ['part3', 'part4']
     * 
     * @return array
     */
    public static function getPathOptions(): array
    {
        $parts = self::getPathArray();
        $options = array_slice($parts, 3, -1);
        return $options;
    }

    /**
     * Check that necessary option value is exists in Url path.
     * 
     * @param string $option Option value to check.
     * 
     * @return bool true if option exists
     */
    public static function hasPathOption(string $option): bool
    {
        $options = self::getPathOptions();
        if (is_array($options) && in_array($option, $options)) {
            return true;
        }
        return false;
    }

    /**
     * Retrieve option value from Url path, by option's index number (starts from 1)
     * 
     * Example: for path '/module/action/option1/option2/option3/' and option index 2,
     * it returns 'option2'
     * 
     * @param int $ind option's index number
     * 
     * @return mixed Option's value or default if not exists
     */
    public static function getPathOptionByIndex(int $ind = 1, $default = null)
    {
        $options = self::getPathOptions();
        if (!is_array($options)) {
            return $default;
        }
        $option = !empty($options[$ind - 1]) ? (string) $options[$ind - 1] : $default;
        return $option;
    }

    /**
     * Split current Url path into array.
     * First two parts frequently defines 'controller/action'.
     * 
     * @return array
     */
    private static function getPathArray(): array
    {
        $path = explode('/', static::$uri->getPath());
        $path[0] = $path;
        return $path;
    }

    private function __construct()
    {
        // Can not instantiate
    }
}