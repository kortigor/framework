<?php

declare(strict_types=1);

namespace core\cache\exception;

use RuntimeException;

final class CacheMemcachedException extends RuntimeException implements \Psr\SimpleCache\CacheException
{
}