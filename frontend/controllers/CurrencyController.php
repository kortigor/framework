<?php

declare(strict_types=1);

namespace frontend\controllers;

use DomainException;
use frontend\base\BaseFrontendController;
use core\event\ProviderReflection;
use utils\cbr\CBRAgent;
use customer\entities\Currency;
use customer\listeners\CBRListener;
use customer\events\currency\CBRLoadError;
use customer\events\currency\CBRCurrencyRateNotFound;

class CurrencyController extends BaseFrontendController
{
    /**
     * @var array Currency codes list to update.
     */
    private array $codes;

    /**
     * @var int Number of connection attempts to CBR webservice.
     */
    private int $attempts = 6;

    /**
     * @var int Timeout between connection attempts, in seconds.
     */
    private int $timeout = 10;

    /**
     * @var int Execution time limit, in seconds.
     */
    private int $timeLimit;

    public function __construct()
    {
        parent::__construct();
        $this->codes = array_filter(Currency::VALID_CODES, fn (string $code) => $code !== Currency::BASE_CODE);
        $this->timeLimit = ($this->attempts - 1) * $this->timeout + 30;
        $provider = (new ProviderReflection)->addObjectListeners(new CBRListener);
        $this->getEventManager()->addProvider($provider);
    }

    public function actionCronupdate()
    {
        set_time_limit($this->timeLimit);

        $cbr = new CBRAgent;
        $attempt = 1;
        while (!$isLoaded = $cbr->load() && $attempt <= $this->attempts) {
            $attempt++;
            sleep($this->timeout);
        }

        if ($isLoaded) {
            /** @var Currency[] $collection */
            $collection = Currency::whereIn('code', $this->codes)->get();
            foreach ($collection as $currency) {
                try {
                    $rate = $cbr->get($currency->code);
                    $currency->updateRate($rate);
                    $this->getEventManager()->recordCollection($currency);
                } catch (DomainException) {
                    $this->getEventManager()->recordEvent(new CBRCurrencyRateNotFound($currency->code));
                }
            }
        } else {
            $this->getEventManager()->recordEvent(new CBRLoadError);
        }
    }
}
