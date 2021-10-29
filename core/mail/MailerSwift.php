<?php

declare(strict_types=1);

namespace core\mail;

use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;
use core\interfaces\MailerFacadeInterface;
use core\interfaces\MailMessageFacadeInterface;
use core\web\View;

/**
 * Facade implementation for SwiftMailer with Smtp transport.
 * 
 * @see Swift_Mailer
 * @see Swift_SmtpTransport
 */
class MailerSwift implements MailerFacadeInterface
{
	/**
	 * @var string From name
	 */
	public string $from;

	/**
	 * @var string From email
	 */
	public string $email;

	/**
	 * @var string Smtp host
	 */
	protected string $host;

	/**
	 * @var int Smtp host port
	 */
	protected int $port;

	/**
	 * @var string Smtp encryption
	 */
	protected string $encryption;

	/**
	 * @var string Smtp login
	 */
	protected string $login;

	/**
	 * @var string Smtp password
	 */
	protected string $password;

	/**
	 * @var int Smtp timeout in seconds.
	 */
	protected int $timeout = 30;

	/**
	 * @var string Messages charset
	 */
	protected string $charset;

	/**
	 * @var string View page layout
	 */
	protected string $layout;

	/**
	 * @var View View object to render templates
	 */
	protected View $view;

	/**
	 * @var Swift_Mailer Facaded object
	 */
	protected Swift_Mailer $mailer;

	/**
	 * Constructor.
	 * 
	 * @param Config $config Mailer config.
	 */
	public function __construct(Config $config)
	{
		foreach ($config as $option => $value) {
			$this->$option = $value;
		}

		$transport = (new Swift_SmtpTransport($this->host, $this->port, $this->encryption ?: null))
			->setUsername($this->login)
			->setPassword($this->password)
			->setTimeout($this->timeout);

		$this->mailer = new Swift_Mailer($transport);
		$this->mailer->registerPlugin(new ImageEmbedPlugin($_SERVER['DOCUMENT_ROOT']));
		$this->view = new View($this->layout);
	}

	/**
	 * Get View object to render templates
	 * 
	 * @return View
	 */
	public function getView(): View
	{
		return $this->view;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setFrom(string $name): self
	{
		$this->from = $name;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setEmail(string $address): self
	{
		$this->email = $address;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFrom(): ?string
	{
		return $this->from ?? null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEmail(): ?string
	{
		return $this->email ?? null;
	}


	/**
	 * {@inheritDoc}
	 */
	public function send(MailMessageFacadeInterface $message): int
	{
		$message->setCharset($this->charset);
		if (!$message->getFrom()) {
			$message->setFrom($this->email, $this->from);
		}

		return $this->mailer->send($this->getMessageBehind($message));
	}

	/**
	 * {@inheritDoc}
	 * 
	 * Get `Swift_Mailer` mailer object
	 * 
	 * @return Swift_Mailer
	 */
	public function getBehind(): Swift_Mailer
	{
		return $this->mailer;
	}

	/**
	 * Get Swift_Message object behind MailMessage facade.
	 * 
	 * @param MailMessageFacadeInterface $message
	 * 
	 * @return Swift_Message
	 */
	private function getMessageBehind(MailMessageFacadeInterface $message): Swift_Message
	{
		return $message->getBehind();
	}
}