<?php

declare(strict_types=1);

namespace customer\events\currency;

/**
 * Currency update error event.
 * It meant that errors occured during save currency rate.
 */
class CurencyUpdateError extends AbstractCurrencyEvent
{
    const EVENT_NAME = 'Ошибка обновления курса валюты';

    /**
     * Constructor.
     * 
     * @param string $code Currency code, 3-letter ISO 4217
     * @param string $message Error message
     */
    public function __construct(public string $code, public string $message)
    {
    }
}
