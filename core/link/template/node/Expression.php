<?php

declare(strict_types=1);

namespace core\link\template\node;

use core\link\template\Parser;
use core\link\template\operator\Abstraction as Operator;

/**
 * Expression node implementation
 */
class Expression extends Abstraction
{
    /**
     * Construct
     *
     * @param string $token
     * @param Operator $operator
     * @param Variable[] $variables
     * @param $forwardLookupSeparator Whether to do a forward lookup for a given separator
     *
     * @return void
     */
    public function __construct(string $token, private Operator $operator, private array $variables = [], private $forwardLookupSeparator = '')
    {
        parent::__construct($token);
    }

    /**
     * @return Operator
     */
    public function getOperator(): Operator
    {
        return $this->operator;
    }

    /**
     * @return Variable[]
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * @return string
     */
    public function getForwardLookupSeparator(): string
    {
        return $this->forwardLookupSeparator;
    }

    /**
     * @param string $forwardLookupSeparator
     */
    public function setForwardLookupSeparator(string $forwardLookupSeparator): void
    {
        $this->forwardLookupSeparator = $forwardLookupSeparator;
    }

    /**
     * @inheritDoc
     */
    public function expand(Parser $parser, array $params = []): ?string
    {
        $data = [];

        // check for variable modifiers
        foreach ($this->variables as $var) {
            $val = $this->operator->expand($parser, $var, $params);

            // skip null value
            if ($val !== null) {
                $data[] = $val;
            }
        }

        return $data ? $this->operator->first . implode($this->operator->sep, $data) : null;
    }

    /**
     * @inheritDoc
     */
    public function match(Parser $parser, string $uri, array $params = [], bool $strict = false): ?array
    {
        // check expression operator first
        if ($this->operator->id && $uri[0] !== $this->operator->id) {
            return array($uri, $params);
        }

        // remove operator from input
        if ($this->operator->id) {
            $uri = substr($uri, 1);
        }

        foreach ($this->sortVariables($this->variables) as $var) {
            $regex = '#' . $this->operator->toRegex($parser, $var) . '#';
            $val = null;

            // do a forward lookup and get just the relevant part
            $remainingUri = '';
            $preparedUri = $uri;
            if ($this->forwardLookupSeparator) {
                $lastOccurrenceOfSeparator = stripos($uri, $this->forwardLookupSeparator);
                $preparedUri = substr($uri, 0, $lastOccurrenceOfSeparator);
                $remainingUri = substr($uri, $lastOccurrenceOfSeparator);
            }

            if (preg_match($regex, $preparedUri, $match)) {
                // remove matched part from input
                $preparedUri = preg_replace($regex, '', $preparedUri, 1);
                $val = $this->operator->extract($parser, $var, $match[0]);
            }

            // if strict is given, quit immediately when there's no match
            else if ($strict) {
                return null;
            }

            $uri = $preparedUri . $remainingUri;

            $params[$var->getToken()] = $val;
        }

        return [$uri, $params];
    }

    /**
     * Sort variables before extracting data from uri.
     * Have to sort vars by non-explode to explode.
     *
     * @param array $vars
     * @return Variable[]
     */
    protected function sortVariables(array $vars): array
    {
        usort($vars, fn (Variable $a, Variable $b) => $a->modifier >= $b->modifier ? 1 : -1);
        return $vars;
    }
}