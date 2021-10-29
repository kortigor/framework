<?php

declare(strict_types=1);

namespace customer\events\currency;

/**
 * CBR currency rate error load event.
 * It meant that data delivered from webservice does not contains specific currency rate.
 * But was no errors during load currencies data from CBR webservice.
 */
class CBRCurrencyRateNotFound extends AbstractCurrencyEvent
{
    const EVENT_NAME = 'Нет данных о курсе валюты';

    /**
     * Constructor.
     * 
     * @param string $code Currency code, 3-letter ISO 4217
     */
    public function __construct(public string $code)
    {
    }
}
