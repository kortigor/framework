<?php

declare(strict_types=1);

namespace customer\listeners;

use core\helpers\FileHelper;
use customer\events\currency\AbstractCurrencyEvent;
use customer\events\currency\CBRLoadError;
use customer\events\currency\CBRCurrencyRateNotFound;
use customer\events\currency\CurencyUpdateSuccess;
use customer\events\currency\CurencyUpdateError;
use customer\factory\MailMessageFactory;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Stream;

class CBRListener
{
    /**
     * @var MailMessageFactory
     */
    private MailMessageFactory $factory;

    public function __construct()
    {
        $this->factory = new MailMessageFactory;
    }

    public function onLoadError(CBRLoadError $event): void
    {
        $text = sprintf(
            '[%s] %s',
            $event->getTime()->format('H:i:s d.m.Y'),
            $event::EVENT_NAME,
        );

        printr($text);
        $this->errorLog($event::EVENT_NAME, $event);
        $this->factory->createNotify($event::EVENT_NAME, $text)->send();
    }

    public function onRateError(CBRCurrencyRateNotFound $event): void
    {
        $text = sprintf(
            '[%s] %s: %s',
            $event->getTime()->format('H:i:s d.m.Y'),
            $event::EVENT_NAME,
            $event->code
        );

        printr($text);
        $this->errorLog($event::EVENT_NAME . ' ' . $event->code, $event);
        $this->factory->createNotify($event::EVENT_NAME, $text)->send();
    }

    public function onCurrencyUpdateError(CurencyUpdateError $event): void
    {
        $text = sprintf(
            '[%s] %s: %s | %s',
            $event->getTime()->format('H:i:s d.m.Y'),
            $event::EVENT_NAME,
            $event->code,
            $event->message,
        );

        printr($text);
        $this->errorLog($event::EVENT_NAME . ' ' . $event->code, $event);
        $this->factory->createNotify($event::EVENT_NAME, $text)->send();
    }

    public function onCurrencyUpdateSuccess(CurencyUpdateSuccess $event): void
    {
        $text = sprintf(
            '[%s] %s: %s | %02.4f',
            $event->getTime()->format('H:i:s d.m.Y'),
            $event::EVENT_NAME,
            $event->code,
            $event->rate,
        );

        printr($text);
    }

    private function errorLog(string $message, AbstractCurrencyEvent $event): void
    {
        $logger = new Logger;
        $date = $event->getTime()->format('Y-m-d');
        $file = FileHelper::getFilePath(c('main.logPath'), "currency_{$date}.error.log");
        $writer = new Stream($file);
        $logger->addWriter($writer);
        $logger->err($message);
    }
}