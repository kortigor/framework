<?php

declare(strict_types=1);

namespace customer\events\currency;

/**
 * CBR rates error load event.
 * It meant that was errors during load currencies data from CBR webservice.
 */
class CBRLoadError extends AbstractCurrencyEvent
{
    const EVENT_NAME = 'Ошибка загрузки курсов валют';
}
