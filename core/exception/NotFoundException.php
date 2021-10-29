<?php

declare(strict_types=1);

namespace core\exception;

/**
 * Generic exception when some entitity is not found.
 */
class NotFoundException extends BaseException
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
        return 'Entity not found';
    }
}
