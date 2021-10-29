<?php

declare(strict_types=1);

namespace core\http;

use Psr\Http\Message\UriInterface;

/**
 * Compares URIs using different methods.
 *
 * @author Igor Kort
 */
final class UriComparator
{
    const STRICT = 'strict';

    const PATH = 'byPath';

    const PATH_LEVEL = 'byPathLevel';

    const QUERY_ENTRY = 'byQueryEntry';

    /**
     * Compare two URIs by full compliance.
     * 
     * Example:
     *  - uri1 'file.php' equal to uri2 'file.php';
     *  - uri1 'http://example.com/file.php' NOT equal to uri2 'http://other.com/file.php';
     *  - uri1 'file.php?aaa=bbb' NOT equal to uri2 'file.php';
     *  - uri1 'file.php?aaa=bbb' equal to uri2 'file.php?aaa=bbb';
     *  - uri1 'file.php?aaa=bbb&ccc=ddd' NOT equal to uri2 'file.php?aaa=bbb';
     *  - uri1 'file.php?xxx=bbb&ccc=ddd' equal to uri2 'file.php?xxx=bbb&ccc=ddd';
     *  - uri1 'file.php?&ccc=ddd&xxx=bbb' equal to uri2 'file.php?xxx=bbb&ccc=ddd';
     * 
     * @param UriInterface $uri1
     * @param UriInterface $uri2
     * 
     * @return bool True if URIs considered as equal.
     */
    public static function strict(UriInterface $uri1, UriInterface $uri2): bool
    {
        return UriNormalizer::isEquivalent(
            $uri1,
            $uri2,
            UriNormalizer::PRESERVING_NORMALIZATIONS | UriNormalizer::SORT_QUERY_PARAMETERS
        );
    }

    /**
     * Compare two URIs by host and path compliance.
     * 
     * Example:
     *  - uri1 'file.php' equal to uri2 'file.php';
     *  - uri1 'http://example.com/file.php' NOT equal to uri2 'http://other.com/file.php';
     *  - uri1 'file.php?aaa=bbb' equal to uri2 'file.php';
     *  - uri1 'file.php?aaa=bbb' equal to uri2 'file.php?ddd=fff';
     * 
     * @param UriInterface $uri1
     * @param UriInterface $uri2
     * 
     * @return bool True if URIs considered as equal.
     */
    public static function byPath(UriInterface $uri1, UriInterface $uri2): bool
    {
        return UriNormalizer::isEquivalent(
            $uri1->withScheme('')->withQuery('')->withFragment('')->withPort(null),
            $uri2->withScheme('')->withQuery('')->withFragment('')->withPort(null),
        );
    }

    /**
     * Compare two URIs by host and part of path compliance.
     * 
     * @param UriInterface $uri1
     * @param UriInterface $uri2
     * @param int $level Path level to compare
     * 
     *  * Example:
     *  - uri1 '/first/second/third/' equal to uri2 '/first/second/thirdzzz/' if $level=1|2, but not equal if level=3;
     * 
     * @return bool
     */
    public static function byPathLevel(UriInterface $uri1, UriInterface $uri2, int $level): bool
    {
        $path1levels = array_slice(
            array_filter(explode('/', $uri1->getPath())),
            0,
            $level
        );

        $path2levels = array_slice(
            array_filter(explode('/', $uri2->getPath())),
            0,
            $level
        );

        return $path1levels === $path2levels && $uri1->getHost() === $uri2->getHost();
    }

    /**
     * Compare two URIs by host, path, and uri2 query full entry to $uri1 query.
     * 
     * Example:
     *  - uri1 'file.php' equal to uri2 'file.php';
     *  - uri1 'http://example.com/file.php' NOT equal to uri2 'http://other.com/file.php';
     *  - uri1 'file.php?aaa=bbb' NOT equal to uri2 'file.php';
     *  - uri1 'file.php?aaa=bbb' equal to uri2 'file.php?aaa=bbb';
     *  - uri1 'file.php?aaa=bbb' NOT equal to uri2 'file.php?aaa=ccc';
     *  - uri1 'file.php?aaa=bbb&ccc=ddd' equal to uri2 'file.php?aaa=bbb';
     *  - uri1 'file.php?xxx=bbb&ccc=ddd' NOT equal to uri2 'file.php?aaa=bbb';
     *  - uri1 'file.php?xxx=bbb&ccc=ddd' NOT equal to uri2 'file.php?aaa=bbb';
     * 
     * @param UriInterface $uri1
     * @param UriInterface $uri2
     * 
     * @return bool True if URIs considered as equal.
     */
    public static function byQueryEntry(UriInterface $uri1, UriInterface $uri2): bool
    {
        $query1Params = explode('&', $uri1->getQuery());
        $query2Params = explode('&', $uri2->getQuery());

        if (array_diff($query2Params, $query1Params)) {
            return false;
        }

        return static::byPath($uri1, $uri2);
    }

    private function __construct()
    {
        // cannot be instantiated
    }
}
