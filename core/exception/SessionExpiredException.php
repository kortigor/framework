<?php

declare(strict_types=1);

namespace core\exception;

/**
 * SessionExpiredException represents an exception caused by session expiration.
 */
class SessionExpiredException extends BaseException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Session expired.';
    }
}
