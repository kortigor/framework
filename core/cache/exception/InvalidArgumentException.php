<?php

declare(strict_types=1);

namespace core\cache\exception;

use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

final class InvalidArgumentException extends \InvalidArgumentException implements PsrInvalidArgumentException
{
}