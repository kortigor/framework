<?php

declare(strict_types=1);

namespace customer\events\currency;

/**
 * Currency success update event.
 * It meant that exchange rate for specific currency rate was successfully updated.
 */
class CurencyUpdateSuccess extends AbstractCurrencyEvent
{
    const EVENT_NAME = 'Обновлён курс валюты';

    /**
     * Constructor.
     * 
     * @param string $code Currency code, 3-letter ISO 4217
     * @param float $rate Currency rate
     */
    public function __construct(public string $code, public float $rate)
    {
    }
}
