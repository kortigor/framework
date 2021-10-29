<?php

declare(strict_types=1);

namespace core\link\template\operator;

use Exception;
use core\link\template\Parser;
use core\link\template\node\Variable;

/**
 * .------------------------------------------------------------------.
 * |          NUL     +      .       /       ;      ?      &      #   |
 * |------------------------------------------------------------------|
 * | first |  ""     ""     "."     "/"     ";"    "?"    "&"    "#"  |
 * | sep   |  ","    ","    "."     "/"     ";"    "&"    "&"    ","  |
 * | named | false  false  false   false   true   true   true   false |
 * | ifemp |  ""     ""     ""      ""      ""     "="    "="    ""   |
 * | allow |   U     U+R     U       U       U      U      U     U+R  |
 * `------------------------------------------------------------------'
 *
 * named = false
 * | 1   |    {/list}    /red,green,blue                  | {$value}*(?:,{$value}+)*
 * | 2   |    {/list*}   /red/green/blue                  | {$value}+(?:{$sep}{$value}+)*
 * | 3   |    {/keys}    /semi,%3B,dot,.,comma,%2C        | /(\w+,?)+
 * | 4   |    {/keys*}   /semi=%3B/dot=./comma=%2C        | /(?:\w+=\w+/?)*
 * named = true
 * | 1   |    {?list}    ?list=red,green,blue             | {name}=(?:\w+(?:,\w+?)*)*
 * | 2   |    {?list*}   ?list=red&list=green&list=blue   | {name}+=(?:{$value}+(?:{sep}{name}+={$value}*))*
 * | 3   |    {?keys}    ?keys=semi,%3B,dot,.,comma,%2C   | (same as 1)
 * | 4   |    {?keys*}   ?semi=%3B&dot=.&comma=%2C        | (same as 2)
 *
 * UNRESERVED
 * ----------
 * RFC 1738 ALPHA | DIGIT | "-" | "." | "_" |     | "$" | "+" | "!" | "*" | "'" | "(" | ")" | ","
 * RFC 3986 ALPHA | DIGIT | "-" | "." | "_" | "~"
 * RFC 6570 ALPHA | DIGIT | "-" | "." | "_" | "~"
 *
 * RESERVED
 * --------
 * RFC 1738 ":" | "/" | "?" |                 | "@" | "!" | "$" | "&" | "'" | "(" | ")" | "*" | "+" | "," | ";" | "=" | "-" | "_" | "." | 
 * RFC 3986 ":" | "/" | "?" | "#" | "[" | "]" | "@" | "!" | "$" | "&" | "'" | "(" | ")" | "*" | "+" | "," | ";" | "="
 * RFC 6570 ":" | "/" | "?" | "#" | "[" | "]" | "@" | "!" | "$" | "&" | "'" | "(" | ")" | "*" | "+" | "," | ";" | "="
 *
 * PHP_QUERY_RFC3986 was added in PHP 5.4.0
 */
abstract class Abstraction
{
    /**
     * @var array gen-delims | sub-delims
     */
    protected const RESERVED_CHARS = [
        '%3A' => ':',
        '%2F' => '/',
        '%3F' => '?',
        '%23' => '#',
        '%5B' => '[',
        '%5D' => ']',
        '%40' => '@',
        '%21' => '!',
        '%24' => '$',
        '%26' => '&',
        '%27' => "'",
        '%28' => '(',
        '%29' => ')',
        '%2A' => '*',
        '%2B' => '+',
        '%2C' => ',',
        '%3B' => ';',
        '%3D' => '=',
    ];

    /**
     * @var array Operator types constructor arguments.
     * start - Variable offset position, level-2 operators start at 1 (exclude operator itself, e.g. {?query})
     * first - If variables found, prepend this value to it
     * named - Whether or not the expansion includes the variable or key name
     * reserved - union of (unreserved / reserved / pct-encoded)
     */
    protected const TYPES = [
        '' => [
            'sep'   => ',',
            'named' => false,
            'empty' => '',
            'reserved' => false,
            'start' => 0,
            'first' => '',
        ],
        '+' => [
            'sep'   => ',',
            'named' => false,
            'empty' => '',
            'reserved' => true,
            'start' => 1,
            'first' => '',
        ],
        '.' => [
            'sep'   => '.',
            'named' => false,
            'empty' => '',
            'reserved' => false,
            'start' => 1,
            'first' => '.',
        ],
        '/' => [
            'sep'   => '/',
            'named' => false,
            'empty' => '',
            'reserved' => false,
            'start' => 1,
            'first' => '/',
        ],
        ';' => [
            'sep'   => ';',
            'named' => true,
            'empty' => '',
            'reserved' => false,
            'start' => 1,
            'first' => ';',
        ],
        '?' => [
            'sep'   => '&',
            'named' => true,
            'empty' => '=',
            'reserved' => false,
            'start' => 1,
            'first' => '?',
        ],
        '&' => [
            'sep'   => '&',
            'named' => true,
            'empty' => '=',
            'reserved' => false,
            'start' => 1,
            'first' => '&',
        ],
        '#' => [
            'sep'   => ',',
            'named' => false,
            'empty' => '',
            'reserved' => true,
            'start' => 1,
            'first' => '#',
        ],
    ];

    /**
     * RFC 3986 Allowed path characters regex except the path delimiter '/'.
     *
     * @var string
     */
    protected const PATH_REGEX = '(?:[a-zA-Z0-9\-\._~!\$&\'\(\)\*\+,;=%:@]+|%(?![A-Fa-f0-9]{2}))';

    /**
     * RFC 3986 Allowed query characters regex except the query parameter delimiter '&'.
     *
     * @var string
     */
    protected const QUERY_REGEX = '(?:[a-zA-Z0-9\-\._~!\$\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))';

    /**
     * @var array
     */
    protected static array $loaded = [];

    /**
     * Constructor
     *
     * @param string $id
     * @param bool $named Whether or not the expansion includes the variable or key name
     * @param string $sep
     * @param string $empty
     * @param bool $reserved Union of (unreserved / reserved / pct-encoded)
     * @param int $start Variable offset position, level-2 operators start at 1 (exclude operator itself, e.g. {?query})
     * @param string $first If variables found, prepend this value to it
     *
     * @return void
     */
    public function __construct(
        public string $id,
        public bool $named,
        public string $sep,
        public string $empty,
        public bool $reserved,
        public int $start,
        public string $first
    ) {
    }

    /**
     * Convert operator to REGEXP
     * 
     * @param Parser $parser
     * @param Variable $var
     * 
     * @return string regexp
     */
    abstract public function toRegex(Parser $parser, Variable $var): string;

    /**
     * @param Parser $parser
     * @param Variable $var
     * @param array $params
     * 
     * @return mixed
     */
    public function expand(Parser $parser, Variable $var, array $params = []): mixed
    {
        $name = $var->name;
        $is_explode = in_array($var->modifier, ['*', '%']);

        // skip null
        if (!isset($params[$name])) {
            return null;
        }

        $val = $params[$name];

        // This algorithm is based on RFC6570 http://tools.ietf.org/html/rfc6570
        // non-array, e.g. string
        if (!is_array($val)) {
            return $this->expandString($parser, $var, (string) $val);
        }

        // non-explode ':'
        else if (!$is_explode) {
            return $this->expandNonExplode($parser, $var, $val);
        }

        // explode '*', '%'
        else {
            return $this->expandExplode($parser, $var, $val);
        }
    }

    /**
     * @param Parser $parser
     * @param Variable $var
     * @param string $val
     * 
     * @return string
     */
    public function expandString(Parser $parser, Variable $var, string $val): string
    {
        if ($var->modifier === ':') {
            $val = substr($val, 0, (int) $var->value);
        }

        return $this->encode($parser, $var, $val);
    }

    /**
     * Non explode modifier ':'
     *
     * @param Parser $parser
     * @param Variable $var
     * @param array $val
     * @return null|string
     */
    public function expandNonExplode(Parser $parser, Variable $var, array $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        return $this->encode($parser, $var, $val);
    }

    /**
     * Explode modifier '*', '%'
     *
     * @param Parser $parser
     * @param Variable $var
     * @param array $val
     * @return null|string
     */
    public function expandExplode(Parser $parser, Variable $var, array $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        return $this->encode($parser, $var, $val);
    }

    /**
     * Encodes variable according to spec (reserved or unreserved)
     *
     * @param Parser $parser
     * @param Variable $var
     * @param string|array $values
     *
     * @return string encoded string
     */
    public function encode(Parser $parser, Variable $var, string|array $values): string
    {
        $values = (array) $values;
        $list = isset($values[0]);
        $assoc_sep = '=';
        $sep = $this->sep;

        // non-explode modifier always use ',' as a separator
        if ($var->modifier !== '*') {
            $assoc_sep = $sep = ',';
        }

        array_walk($values, function (&$v, $k) use ($assoc_sep, $list) {
            $encoded = rawurlencode($v);

            // assoc? encode key too
            if (!$list) {
                $encoded = rawurlencode($k) . $assoc_sep . $encoded;
            }

            // rawurlencode is compliant with 'unreserved' set
            if (!$this->reserved) {
                $v = $encoded;
            }

            // decode chars in reserved set
            else {
                $v = str_replace(array_keys(static::RESERVED_CHARS), static::RESERVED_CHARS, $encoded);
            }
        });

        return implode($sep, $values);
    }

    /**
     * Decodes variable value(s)
     *
     * @param Parser $parser
     * @param Variable $var
     * @param string|array $values
     *
     * @return string|array decoded value(s)
     */
    public function decode(Parser $parser, Variable $var, string|array $values): string|array
    {
        $isSingle = is_string($values);
        $values = (array) $values;

        array_walk($values, function (&$v, $k) {
            $v = rawurldecode($v);
        });

        return $isSingle ? reset($values) : $values;
    }

    /**
     * Extracts value from variable
     *
     * @param Parser $parser
     * @param Variable $var
     * @param string $data
     * @return string|array Extracted variable value
     */
    public function extract(Parser $parser, Variable $var, string $data): string|array
    {
        $values = array_filter(explode($this->sep, $data));

        switch ($var->modifier) {
            case '*':
                $collect = [];
                foreach ($values as $val) {
                    if (strpos($val, '=') !== false) {
                        list($k, $v) = explode('=', $val);
                        $collect[$k] = $v;
                    } else {
                        $collect[] = $val;
                    }
                }
                break;

            case ':':
                $collect = $data;
                break;

            default:
                $collect = strpos($data, $this->sep) !== false ? $values : $data;
        }

        return $this->decode($parser, $var, $collect);
    }

    /**
     * Create concrete operator instance
     * 
     * @param string $id
     * 
     * @return static
     */
    public static function createById(string $id): static
    {
        if (!static::isValid($id)) {
            throw new Exception("Invalid operator [$id]");
        }

        if (isset(static::$loaded[$id])) {
            return static::$loaded[$id];
        }

        $op = static::TYPES[$id];
        $op['id'] = $id;
        $class = __NAMESPACE__ . '\\' . ($op['named'] ? 'Named' : 'UnNamed');

        return static::$loaded[$id] = new $class(...$op);
    }

    /**
     * Whether operator is valid.
     * 
     * @param string $id Operator id
     * 
     * @return bool
     */
    public static function isValid(string $id): bool
    {
        return isset(static::TYPES[$id]);
    }

    /**
     * Returns the correct regex given the variable location in the URI
     *
     * @return string
     */
    protected function getRegex(): string
    {
        switch ($this->id) {
            case '?':
            case '&':
            case '#':
                return self::QUERY_REGEX;
            case ';':
            default:
                return self::PATH_REGEX;
        }
    }
}