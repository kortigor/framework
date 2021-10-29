<?php

declare(strict_types=1);

namespace core\link\template\operator;

use Exception;
use core\link\template\Parser;
use core\link\template\node\Variable;

/**
 * | 1   |    {/list}    /red,green,blue                  | {$value}*(?:,{$value}+)*
 * | 2   |    {/list*}   /red/green/blue                  | {$value}+(?:{$sep}{$value}+)*
 * | 3   |    {/keys}    /semi,%3B,dot,.,comma,%2C        | /(\w+,?)+
 * | 4   |    {/keys*}   /semi=%3B/dot=./comma=%2C        | /(?:\w+=\w+/?)*
 */
class UnNamed extends Abstraction
{
    public function toRegex(Parser $parser, Variable $var): string
    {
        $regex = '';
        $value = $this->getRegex();

        if ($var->modifier) {
            switch ($var->modifier) {
                case '*':
                    // 2 | 4
                    $regex = "{$value}+(?:{$this->sep}{$value}+)*";
                    break;
                case ':':
                    $regex = $value . '{0,' . $var->value . '}';
                    break;
                case '%':
                    throw new Exception("% (array) modifier only works with Named type operators e.g. ;,?,&");
                default:
                    throw new Exception("Unknown modifier `{$var->modifier}`");
            }
        } else {
            // 1, 3
            $regex = "{$value}*(?:,{$value}+)*";
        }

        return $regex;
    }
}