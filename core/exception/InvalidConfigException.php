<?php

declare(strict_types=1);

namespace core\exception;

/**
 * InvalidConfigException represents an exception caused by incorrect object configuration.
 *
 */
class InvalidConfigException extends BaseException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Invalid Configuration';
    }
}
