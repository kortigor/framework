<?php

namespace core\runner\exception;

use InvalidArgumentException;
use core\runner\Emitter;

class InvalidEmitterException extends InvalidArgumentException implements ExceptionInterface
{
    /**
     * @var mixed $emitter Invalid emitter type
     */
    public static function forEmitter($emitter): self
    {
        return new self(sprintf(
            '%s can only compose %s implementations; received %s',
            Emitter\EmitterStack::class,
            Emitter\EmitterInterface::class,
            is_object($emitter) ? get_class($emitter) : gettype($emitter)
        ));
    }
}
