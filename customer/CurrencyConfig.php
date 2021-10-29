<?php

declare(strict_types=1);

namespace customer;

use core\validators\Assert;
use core\interfaces\ConfigProviderInterface;
use core\exception\InvalidConfigException;
use customer\entities\Currency;
use Illuminate\Database\Eloquent\Collection;

/**
 * Application currency config
 */
class CurrencyConfig implements ConfigProviderInterface
{
    /**
     * @var array Convertation percents storage. Pairs $code => $percent.
     */
    protected array $percent;

    /**
     * @var array Currencies multipliers storage, with following structure:
     * ```
     * [
     *  // USD->EUR (1USD * 0.8 = 0.8EUR), USD->RUB (1USD * 75.1 = 75.1RUB) ...
     *  'USD' => ['EUR' => 0.8, 'RUB' => 75.1, ...],
     * 
     *  // EUR->USD (1EUR * 1.1 = 1.1USD), EUR->RUB (1EUR * 85.1 = 85.1RUB) ...
     *  'EUR' => ['USD' => 1.1, 'RUB' => 85.1, ...],
     * 
     *  // RUB->EUR (1RUB * 0.013 = 0.013EUR), RUB->USD (1RUB * 0.01 = 0.01USD) ...
     *  'RUB' => ['EUR' => 0.013, 'USD' => 0.01, ...],
     * ...
     * ]
     * ```
     */
    protected array $mult;

    /**
     * @var Collection
     */
    protected Collection $currencies;

    /**
     * Constructor.
     * 
     * @param array $options Associative array of pairs $code => $percent.
     */
    public function __construct(array $options = [])
    {
        $this->currencies = Currency::all();
        foreach ($this->currencies as $currency) {
            $percent = $options[$currency->code] ?? 0.0;
            $this->setPercent($currency->code, $percent);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        if (!isset($this->mult)) {
            $this->mult = $this->getMultipliers();
        }

        return $this->mult;
    }

    /**
     * Convertation percent setter.
     * 
     * @param string $code Currency code.
     * @param float $percent Convertation percent.
     * 
     * @return self
     * @throws InvalidConfigException On trying to set unsupported currency code.
     */
    public function setPercent(string $code, float $percent): self
    {
        Assert::oneOf($code, Currency::VALID_CODES);
        $this->percent[$code] = $percent;
        return $this;
    }

    /**
     * Calculate all currencies multipliers.
     * 
     * @return array
     */
    protected function getMultipliers(): array
    {
        /** @var Currency $currency */
        foreach ($this->currencies as $currency) {
            /** @var Currency $currency2 */
            foreach ($this->currencies as $currency2) {
                if ($currency->isEqualTo($currency2) || $currency->code === Currency::BASE_CODE) {
                    continue;
                }

                $code1 = $currency->code;
                $code2 = $currency2->code;
                $percent1 = $this->percent[$code1];
                $percent2 = $this->percent[$code2];

                $mult[$code1][$code2] = Currency::rateCross($currency, $currency2, $percent1, $percent2);
                $mult[$code1][Currency::BASE_CODE] = $currency->rateBuy($percent1);
                $mult[Currency::BASE_CODE][$code1] = $currency->toBaseBuy($percent1);
            }
        }

        return $mult ?? [];
    }
}