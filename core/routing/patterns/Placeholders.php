<?php

declare(strict_types=1);

namespace core\routing\patterns;

/**
 * Url argument placeholders and their REGEX patterns.
 */
class Placeholders
{
    /**
     * @var array Regex symbols possible to be used in placeholder regex.
     * 
     * Result of code execution:
     * ```
     * $symb = '\+*?[^]$(){}=!<>|:-.,&'; // Special regexp symbols need to be escaped
     * $regex = '[a-zA-Z0-9' . preg_quote($symb) . ']+'; // Matches by all symbols possible to use in regexp
     * ```
     * @see https://www.php.net/manual/ru/function.preg-quote.php
     */
    const REGEX = '[a-zA-Z0-9\\\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:\-\.,&]+';

    /**
     * @var array Argument placeholders and their regex allowed to use in the url patterns.
     */
    const TYPES = [
        'num' => '[0-9]+',
        'str' => '[a-zA-Z\.\-_%]+',
        'any' => '[a-zA-Z0-9\.\-_%]+',
        'uuid' => '[a-fA-F0-9]{8}(-[a-fA-F0-9]{4}){4}[a-fA-F0-9]{8}',
        'slug' => '[a-zA-Z0-9\-]+',
    ];
}