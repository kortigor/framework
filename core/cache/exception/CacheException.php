<?php

declare(strict_types=1);

namespace core\cache\exception;

use RuntimeException;
use Throwable;

abstract class CacheException extends RuntimeException implements \Psr\SimpleCache\CacheException
{
    public function __construct(private string $key, string $message = '', int $code = 0, Throwable $previous = null)
    {
        $this->key = $key;
        parent::__construct($message, $code, $previous);
    }

    public function getKey(): string
    {
        return $this->key;
    }
}