<?php

declare(strict_types=1);

namespace customer\entities;

use Throwable;
use RuntimeException;
use core\event\EventStorableInterface;
use core\orm\ActiveRecord;
use customer\events\currency\CurencyUpdateSuccess;
use customer\events\currency\CurencyUpdateError;

/**
 * @property int $id Currency Id.
 * @property string $code Currency code, 3-letter ISO 4217.
 * @property float $rate Currency CBR exchange rate.
 * @property \Carbon\Carbon $created_at Record creation date.
 * @property \Carbon\Carbon $updated_at Record last update date.
 */
class Currency extends ActiveRecord implements EventStorableInterface
{
    use EventTrait;

    const VALID_CODES = ['EUR', 'USD', 'RUB'];

    const BASE_CODE = 'RUB';

    const ROUND_PRECISION = 4;

    const ROUND_METHOD = PHP_ROUND_HALF_UP;

    public bool $validateBeforeSave = true;

    protected $table = 'currency';

    protected $fillable = [
        'code',
        'rate',
    ];

    protected $casts = [
        'rate' => 'float',
    ];

    public function rules(): array
    {
        return [
            [['code', 'rate'], 'required'],
            ['code', 'oneOf' => [self::VALID_CODES]],
            ['rate', 'float'],
        ];
    }

    public function normalizators(): array
    {
        return [
            ['rate', 'normalizeRate'],
        ];
    }

    /**
     * Update currency rate.
     * 
     * @param float $rate New currency rate.
     * 
     * @return bool True on update success.
     */
    public function updateRate(float $rate): bool
    {
        try {
            $this->rate = $rate;
            if (!$this->save()) {
                throw new RuntimeException('Unknown error while currency rate updating.');
            }
            $this->recordEvent(new CurencyUpdateSuccess($this->code, $this->rate));
            return true;
        } catch (Throwable $e) {
            $this->recordEvent(new CurencyUpdateError($this->code, $e->getMessage()));
            return false;
        }
    }

    /**
     * Normalize values to MySQL DECIMAL, i.e. 120 233,25 => 120233.25
     * 
     * @param string $value
     * 
     * @return float Normalized value
     */
    public function normalizeRate(string $value): float
    {
        $value = formatDecimal($value, self::ROUND_PRECISION);
        return floatval($value);
    }

    /**
     * Get rate with convertation percent to buy this currency (RUB->currency).
     * 
     * Example:
     *  - This currency is USD.
     *  - Need to buy USD for RUB (RUB->USD).
     *  - Have to pay RUB for USD at the rate: CBR + percent
     * 
     * @param float $percent Number of percents, like 2 or 2.333 etc...
     * 
     * @return float
     */
    public function rateBuy(float $percent): float
    {
        return $this->ratePercent($percent, true);
    }

    /**
     * Get rate with convertation percent to sale this currency (currency->RUB).
     * 
     * Example:
     *  - This currency is USD.
     *  - Need to sale USD for RUB (USR->RUB).
     *  - Get RUB for USD at the rate: CBR - percent
     * 
     * @param float $percent Number of percents, like 2 or 2.333 etc...
     * 
     * @return float
     */
    public function rateSale(float $percent): float
    {
        return $this->ratePercent($percent, false);
    }

    /**
     * Convert one currency unit to base currency (RUB) with convertation percent.
     * 
     * You have currency in USD and just need to convert in RUB.
     * 
     * @param float $percent Number of percents, like 2 or 2.333 etc...
     * 
     * @return float
     */
    public function toBaseSale(float $percent = 0): float
    {
        $value = 1 / $this->ratePercent($percent, true);
        return round($value, self::ROUND_PRECISION, self::ROUND_METHOD);
    }

    /**
     * Convert some currency units to base currency (RUB) with convertation percent to get needed sum in RUB.
     * 
     * You have currency in USD, need to buy something for known price in RUB,
     * so need to convert USD->RUB with this method.
     * 
     * @param float $percent Number of percents, like 2 or 2.333 etc...
     * 
     * @return float
     */
    public function toBaseBuy(float $percent = 0): float
    {
        $value = 1 / $this->ratePercent($percent, false);
        return round($value, self::ROUND_PRECISION, self::ROUND_METHOD);
    }

    /**
     * Check currency equal to other currency.
     * 
     * @param self $other
     * 
     * @return bool
     */
    public function isEqualTo(self $other): bool
    {
        return $this->code === $other->code;
    }

    /**
     * Get rate with convertation percent
     * 
     * @param float $percent Number of percents, like 2 or 2.333 etc...
     * @param bool $toBuy Convert to buy currency:
     * - true: RUB->USD, need to pay RUB by rate CBR + percent
     * - false: to buy USD->RUB, for USD receive by rate CBR - percent
     * 
     * @return float
     */
    private function ratePercent(float $percent, bool $toBuy = true): float
    {
        $perc = $this->rate * $percent / 100;
        $value = $toBuy ? $this->rate + $perc : $this->rate - $perc;
        return round($value, self::ROUND_PRECISION, self::ROUND_METHOD);
    }

    /**
     * Convert rate between two kinds of currency
     * 
     * @param self $orig Original currency
     * @param self $dst Destination currency
     * @param float $origPerc Number of percents for `$orig`, like 2 or 2.333 etc...
     * @param float $dstPerc Number of percents for `$dst`, like 2 or 2.333 etc...
     * 
     * @return float
     */
    public static function rateCross(self $orig, self $dst, float $origPerc = 0, float $dstPerc = 0): float
    {
        if ($orig->isEqualTo($dst)) {
            return 1;
        }

        $rateOrig2Base = $orig->toBaseSale($origPerc);
        $rateBase2Dst = $dst->rateSale($dstPerc);
        $value = 1 / ($rateOrig2Base * $rateBase2Dst);
        return round($value, self::ROUND_PRECISION, self::ROUND_METHOD);
    }
}
