<?php

declare(strict_types=1);

namespace core\web;

use core\interfaces\KernelInterface;
use core\interfaces\MailerFacadeInterface;
use core\orm\EloquentOrm;
use core\runner\handler\Handler;
use Laminas\I18n\Translator\Translator;
use customer\DbConfig;
use customer\CurrencyConfig;

/**
 * Web application to run.
 * 
 * @property-read EloquentOrm $orm ORM instance.
 * @property-read Translator $i18n Application translator, see https://docs.laminas.dev/laminas-i18n/translation/. 
 * @property-read MailerFacadeInterface $mail Application mailer instance for general purposes (notifications etc.).
 * @property-read MailerFacadeInterface $mailbulk Application mailer instance for bulk messaging.
 */
class Application extends \core\base\Application
{
	/**
	 * @inheritDoc
	 */
	protected function init(): void
	{
		parent::init();

		// Initialize DB connection
		$this->orm;

		// Initialize database stored configs
		$conf = new DbConfig;
		$this->settings()->addConfig('site', $conf);
		unset($conf);

		$conf = new CurrencyConfig([
			'USD' => c('site.rub_usd_percent'),
			'EUR' => c('site.rub_eur_percent'),
		]);
		$this->settings()->addConfig('currency', $conf);
		unset($conf);
	}

	/**
	 * @inheritDoc
	 */
	protected function getKernel(Handler $handler): KernelInterface
	{
		return new Kernel($handler, $this, $this->container);
	}

	/**
	 * @inheritDoc
	 */
	public function setLocale(string $locale): void
	{
		parent::setLocale($locale);
		$this->i18n->setLocale($locale);
	}
}
