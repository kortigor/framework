<?php

declare(strict_types=1);

namespace utils\cbr;

use DateTimeImmutable;
use DOMDocument;
use DomainException;

/**
 * Load currencies data from CBR webservice.
 * @see https://www.cbr.ru/development/SXML/
 */
class CBRAgent
{
    protected const URL_PATTERN = 'https://www.cbr.ru/scripts/XML_daily.asp?date_req=%s';

    /**
     * @var array
     */
    protected array $list;

    /**
     * @var DateTime Datetime of last load attempt.
     */
    protected DateTimeImmutable $date;

    /**
     * Load currencies data from CBR webservice
     * 
     * @param string|null $date Date in any format acceptable by DateTimeImmutable.
     * 
     * @return bool True if data loaded successfully.
     */
    public function load(string $date = null): bool
    {
        $this->date = new DateTimeImmutable($date ?? 'now');
        $url = $this->getUrl($this->date);
        $xml = new DOMDocument;
        if (!$xml->load($url)) {
            return false;
        }

        $this->list = [];
        $root = $xml->documentElement;
        $items = $root->getElementsByTagName('Valute');
        foreach ($items as $item) {
            $code = $item->getElementsByTagName('CharCode')->item(0)->nodeValue;
            $curs = $item->getElementsByTagName('Value')->item(0)->nodeValue;
            $this->list[$code] = floatval(str_replace(',', '.', $curs));
        }

        return true;
    }

    /**
     * Get CBR currency exchange rate (RUB/Currency).
     * 
     * @param string $code Currency code, 3-letter ISO 4217. EUR, USD, etc...
     * 
     * @return float Exchange rate.
     * 
     * @throws DomainException If currency rate not present in loaded data.
     */
    public function get(string $code): float
    {
        if (!isset($this->list[$code])) {
            throw new DomainException("Currency {$code} does not exists");
        }
        return $this->list[$code];
    }

    /**
     * Get date of load attempt.
     * 
     * @return DateTimeImmutable.
     */
    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * Get CBR webservice url to load data for specified date.
     * 
     * @param DateTimeImmutable $date
     * 
     * @return string
     */
    protected function getUrl(DateTimeImmutable $date): string
    {
        return sprintf(static::URL_PATTERN, $date->format('d.m.Y'));
    }
}