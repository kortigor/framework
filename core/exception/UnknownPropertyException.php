<?php

declare(strict_types=1);

namespace core\exception;

/**
 * UnknownPropertyException represents an exception caused by accessing unknown object properties.
 */
class UnknownPropertyException extends BaseException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Unknown Property';
    }
}
