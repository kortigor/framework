<?php

namespace core\runner\exception;

use RuntimeException;

class EmitterException extends RuntimeException implements ExceptionInterface
{
    public static function forHeadersSent($file, $line): self
    {
        return new self(sprintf('Unable to emit response; headers already sent in %s on line %u.', $file, $line));
    }

    public static function forOutputSent(): self
    {
        return new self('Output has been emitted previously; cannot emit response');
    }
}
