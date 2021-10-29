<?php

declare(strict_types=1);

namespace core\link\template;

/**
 * URI Template implementation based on RFC 6570 URI Template.
 * In addition to URI expansion, it also supports URI extraction.
 * 
 * Forked by Kort from: https://github.com/rize/UriTemplate
 * Made PHP8 compatible, code cleanup.
 * 
 * @see https://datatracker.ietf.org/doc/html/rfc6570
 * @see https://github.com/rize/UriTemplate
 */
class UriTemplate
{
    /**
     * @var string
     */
    protected string $baseUri;

    /**
     * @var Parser
     */
    protected Parser $parser;

    /**
     * Constructor
     * 
     * @param string $baseUri
     * @param array $params
     */
    public function __construct(string $baseUri = '', protected array $params = [])
    {
        $this->baseUri = rtrim($baseUri, '/');
        $this->parser = new Parser;
    }

    /**
     * Expands URI Template
     *
     * @param string $uri URI Template
     * @param array $params URI Template's parameters
     * 
     * @return string Expanded uri
     */
    public function expand(string $uri, array $params = []): string
    {
        $uri = $this->baseUri . $uri;

        // quick check
        if (strpos($uri, '{') === false) {
            return $uri;
        }

        $params += $this->params;
        $result = [];
        $nodes = $this->parser->parse($uri);
        foreach ($nodes as $node) {
            $result[] = $node->expand($this->parser, $params);
        }

        return implode('', $result);
    }

    /**
     * Extracts variables from URI
     *
     * @param string $template
     * @param string $uri
     * @param bool $strict This will perform a full match
     * 
     * @return null|array associarive array of params or null if not match and `$strict` is true
     */
    public function extract(string $template, string $uri, bool $strict = false): ?array
    {
        $params = [];
        $nodes = $this->parser->parse($template);

        foreach ($nodes as $node) {
            // if strict is given, and there's no remaining uri just return null
            if ($strict && !strlen($uri)) {
                return null;
            }

            // uri'll be truncated from the start when a match is found
            $match = $node->match($this->parser, $uri, $params, $strict);
            list($uri, $params) = $match;
        }

        // if there's remaining $uri, matching is failed
        if ($strict && strlen($uri)) {
            return null;
        }

        return $params;
    }

    /**
     * Nodes parser getter
     * 
     * @return Parser
     */
    public function getParser(): Parser
    {
        return $this->parser;
    }
}