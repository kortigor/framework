<?php

declare(strict_types=1);

namespace customer\entities;

class StatusEmployee extends Status
{
    protected static array $list = [
        self::STATUS_ACTIVE => 'Активен',
        self::STATUS_INACTIVE => 'Заблокирован',
    ];
}
