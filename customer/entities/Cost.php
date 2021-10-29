<?php

declare(strict_types=1);

namespace customer\entities;

use core\validators\Assert;

/**
 * Convert price to desired currency considering their exchange rates.
 * 
 * @method string RUB(bool $isFormatted, int $numDecimals = 0) Cost in RUB
 * @method string EUR(bool $isFormatted, int $numDecimals = 2) Cost in EUR
 * @method string USD(bool $isFormatted, int $numDecimals = 2) Cost in USD
 */
class Cost
{
    protected const DEFAULT_DECIMALS = 2;

    protected const DECIMALS = [
        'RUB' => 0,
    ];

    /**
     * Constructor.
     * 
     * @param float $value
     * @param string $code Currency code, 3-letter ISO 4217
     * @param array $mult Array of currency convert multipliers. See \customer\CurrencyConfig
     */
    public function __construct(private float $value, private string $code, private array $mult)
    {
        $this->code = strtoupper($code);
        Assert::oneOf($this->code, Currency::VALID_CODES);
        Assert::keyExists($this->mult, $this->code);
    }

    public function __toString()
    {
        return sprintf('%s&nbsp;%s', $this->{$this->code}(), constant(CurrencySign::class . '::SIGN_' . $this->code));
    }

    /**
     * Cost in specified currency getter.
     * 
     * @param string $name Currency code, 3-letter ISO 4217
     * @param array $arguments
     * 
     * @return string
     * 
     * @see convert()
     */
    public function __call(string $code, array $arguments): string
    {
        $code = strtoupper($code);
        Assert::oneOf($code, Currency::VALID_CODES);
        $isFormatted = $arguments[0] ?? true;
        $numDecimals = match (isset($arguments[1])) {
            true => $arguments[1],
            default => static::DECIMALS[$code] ?? static::DEFAULT_DECIMALS
        };

        return $this->convert($code, $isFormatted, $numDecimals);
    }

    /**
     * Convert currency.
     * 
     * @param string $code Currency to convert, ISO code
     * @param bool $isFormatted Value formatted or no
     * @param int $numDecimals Decimal digits
     * 
     * @return string
     */
    protected function convert(string $code, bool $isFormatted, int $numDecimals): string
    {
        if ($this->code === $code) {
            $mult = 1;
        } else {
            $key = strtoupper($code);
            Assert::keyExists($this->mult[$this->code], $key);
            $mult = $this->mult[$this->code][$key];
        }

        $value = $this->value * $mult;
        $value = round($value, $numDecimals, Currency::ROUND_METHOD);

        return $isFormatted ? $this->format($value, $numDecimals) : (string) $value;
    }

    /**
     * Format cost value.
     * 
     * @param float $value
     * @param int $numDecimals
     * 
     * @return string
     */
    protected function format(float $value, int $numDecimals): string
    {
        return f()->asDecimal($value, $numDecimals);
    }
}
