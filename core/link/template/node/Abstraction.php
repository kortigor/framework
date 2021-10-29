<?php

declare(strict_types=1);

namespace core\link\template\node;

use core\link\template\Parser;

/**
 * Base abstract class for all Nodes
 */
abstract class Abstraction
{
    /**
     * Constructor
     * 
     * @param string $token Node token
     */
    public function __construct(private string $token)
    {
    }

    /**
     * Expands URI template
     *
     * @param Parser $parser
     * @param array $params
     * @return null|string
     */
    public function expand(Parser $parser, array $params = []): ?string
    {
        return $this->token;
    }

    /**
     * Matches given URI against current node
     *
     * @param Parser $parser
     * @param string $uri
     * @param array  $params
     * @param bool $strict
     * @return null|array `uri and params` or `null` if not match and $strict is true
     */
    public function match(Parser $parser, string $uri, array $params = [], bool $strict = false): ?array
    {
        // match literal string from start to end
        $length = strlen($this->token);
        if (substr($uri, 0, $length) === $this->token) {
            $uri = substr($uri, $length);
        }

        // when there's no match, just return null if strict mode is given
        elseif ($strict) {
            return null;
        }

        return [$uri, $params];
    }

    /**
     * Node token getter
     * 
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }
}