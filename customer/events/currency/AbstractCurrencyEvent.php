<?php

declare(strict_types=1);

namespace customer\events\currency;

use DateTimeImmutable;
use core\event\BaseEvent;

abstract class AbstractCurrencyEvent extends BaseEvent
{
    const EVENT_NAME = 'AbstractCurrencyEvent';

    private DateTimeImmutable $_time;

    public function getTime(): DateTimeImmutable
    {
        if (!isset($this->_time)) {
            $this->_time = new DateTimeImmutable;
        }

        return $this->_time;
    }
}
