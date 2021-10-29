<?php

declare(strict_types=1);

namespace core\exception;

/**
 * Generic exception in object implements a \core\interfaces\ContainerInterface.
 */
class ContainerException extends BaseException
{
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Unable to get entry from DI container';
    }
}
