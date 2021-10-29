<?php

declare(strict_types=1);

namespace core\routing;

/**
 * InvalidRoutingException represents an exception caused by incorrect routing situation.
 *
 */
class InvalidRoutingException extends \Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Invalid Routing';
    }
}