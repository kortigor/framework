<?php

declare(strict_types=1);

namespace core\bootstrap4;

class Template
{
    /**
     * Generate bootstrap4 template for input group with append text
     * 
     * @param string $text
     * 
     * @return string
     */
    public static function inputGroupAppendText(string $text): string
    {
        return "{label}"
            . '<div class="input-group">'
            . "\n{input}\n"
            . '<div class="input-group-append"><span class="input-group-text">' . $text . '</span></div>'
            . '</div>'
            . "\n{hint}\n{error}";
    }

    /**
     * Generate bootstrap4 template for input group with prepend text
     * 
     * @param string $text
     * 
     * @return string
     */
    public static function inputGroupPrependText(string $text): string
    {
        return "{label}"
            . '<div class="input-group">'
            . '<div class="input-group-prepend"><span class="input-group-text">' . $text . '</span></div>'
            . "\n{input}\n"
            . '</div>'
            . "\n{hint}\n{error}";
    }

    /**
     * Generate bootstrap4 template for input group with append button
     * 
     * @param string $text
     * 
     * @return string
     */
    public static function inputGroupAppendButton(string $text): string
    {
        return "{label}"
            . '<div class="input-group">'
            . "\n{input}\n"
            . '<div class="input-group-append">' . $text . '</div>'
            . '</div>'
            . "\n{hint}\n{error}";
    }
}
