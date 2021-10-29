<?php

declare(strict_types=1);

namespace core\middleware\formatter;

final class ContentNotBeFormattedException extends \RuntimeException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Content can not be formatted';
    }
}