<?php

declare(strict_types=1);

namespace core\link\template;

use Exception;
use core\link\template\node\Abstraction as Node;
use core\link\template\node\Expression;
use core\link\template\node\Variable;
use core\link\template\node\Literal;
use core\link\template\operator\Abstraction as Operator;
use core\link\template\operator\UnNamed;

/**
 * Uri templates parser
 */
class Parser
{
    /**
     * Parses URI Template and returns nodes
     *
     * @param string $template Uri template
     * @return Node[]
     */
    public function parse(string $template): array
    {
        $tokens = $this->getTokens($template);
        $nodes = [];
        foreach ($tokens as $token) {
            $node = $this->createNode($token);

            // if current node has dot separator that requires a forward lookup
            // for the previous node iff previous node's operator is UnNamed
            if ($node instanceof Expression && $node->getOperator()->id === '.') {
                if (count($nodes) > 0) {
                    $previousNode = $nodes[count($nodes) - 1];
                    if ($previousNode instanceof Expression && $previousNode->getOperator() instanceof UnNamed) {
                        $previousNode->setForwardLookupSeparator($node->getOperator()->id);
                    }
                }
            }

            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * Split template to tokens
     * 
     * @param string $template
     * 
     * @return string[] array of tokens
     */
    protected function getTokens(string $template): array
    {
        return preg_split('#(\{[^\}]+\})#', $template, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Create node
     * 
     * @param string $token
     * @return Node
     */
    protected function createNode(string $token): Node
    {
        // Literal string
        if ($this->isLiteral($token)) {
            return $this->createLiteralNode($token);
        }

        // Remove `{}` from expression and parse it
        return $this->parseExpression(substr($token, 1, -1));
    }

    /**
     * Whether token is just literal string, no expressions
     * 
     * @param string $token
     * 
     * @return bool
     */
    protected function isLiteral(string $token): bool
    {
        return $token[0] !== '{';
    }

    /**
     * Parse expression and create expression node.
     * 
     * @param string $expression
     * 
     * @return Expression
     */
    protected function parseExpression(string $expression): Expression
    {
        $token = $expression;
        $operator = $this->parseOperatorId($expression);

        // remove operator from token if exists e.g. '?'
        if ($operator) {
            $token = substr($token, strlen($operator));
        }

        // parse variables
        $vars = [];
        foreach (explode(',', $token) as $var) {
            $vars[] = $this->parseVariable($var);
        }

        return $this->createExpressionNode($token, $this->createOperatorNode($operator), $vars);
    }

    /**
     * Parse operator from expression
     * 
     * @param string $expression Expression to parse
     * 
     * @return string Valid operator id or empty string if no operator
     */
    protected function parseOperatorId(string $expression): string
    {
        $operator = $expression[0];

        // not a valid operator?
        if (!Operator::isValid($operator)) {
            // not valid chars?
            if (!preg_match('#(?:[A-z0-9_\.]|%[0-9a-fA-F]{2})#', $expression)) {
                throw new Exception("Invalid operator [$operator] found at {$expression}");
            }

            // default operator
            $operator = '';
        }

        return $operator;
    }

    protected function parseVariable(string $var): Variable
    {
        $var = trim($var);
        $val = null;
        $modifier = '';

        // check for operator (:) / explode (*) / array (%) modifier
        if (strpos($var, ':') !== false) {
            $modifier = ':';
            list($varname, $val) = explode(':', $var);

            // error checking
            if (!is_numeric($val)) {
                throw new Exception("Value for `:` modifier must be numeric value [$varname:$val]");
            }
        }

        switch ($last = substr($var, -1)) {
            case '*':
            case '%':
                // there can be only 1 modifier per var
                if ($modifier) {
                    throw new Exception("Multiple modifiers per variable are not allowed [$var]");
                }

                $modifier = $last;
                $var = substr($var, 0, -1);
                break;
        }

        return $this->createVariableNode($var, $modifier, $val);
    }

    protected function createVariableNode(string $token, $modifier, $val): Variable
    {
        return new Variable($token, $modifier, $val);
    }

    protected function createExpressionNode(string $token, Operator $operator, array $vars = []): Expression
    {
        return new Expression($token, $operator, $vars);
    }

    protected function createLiteralNode(string $token): Literal
    {
        return new Literal($token);
    }

    protected function createOperatorNode(string $id): Operator
    {
        return Operator::createById($id);
    }
}