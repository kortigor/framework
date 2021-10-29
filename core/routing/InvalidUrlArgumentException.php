<?php

declare(strict_types=1);

namespace core\routing;

/**
 * InvalidUrlArgumentException represents an exception caused by incorrect url generation.
 *
 */
class InvalidUrlArgumentException extends \Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Invalid Url Argument';
    }
}