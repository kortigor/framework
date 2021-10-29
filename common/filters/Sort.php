<?php

declare(strict_types=1);

namespace common\filters;

use core\orm\QueryFilterSort;

class Sort extends QueryFilterSort
{
    public array $default = ['order'];

    public bool $enableArrayValues = true;
}