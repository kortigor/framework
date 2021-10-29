<?php

declare(strict_types=1);

namespace core\interfaces;

use core\orm\Config;

interface OrmInterface
{
    public function __construct(Config $config);

    public function getConnection();
}