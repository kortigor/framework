<?php

declare(strict_types=1);

namespace core\mail;

use Swift_Message;
use Swift_Attachment;
use core\exception\InvalidConfigException;
use core\interfaces\MailMessageFacadeInterface;
use core\interfaces\MailerFacadeInterface;

/**
 * Facade implementation for SwiftMailer's [Swift_Message]
 * 
 * @see Swift_Message
 */
class MailMessageSwift implements MailMessageFacadeInterface
{
	/**
	 * @var Swift_Message composed message
	 */
	protected Swift_Message $message;

	/**
	 * @var MailerSwift Mailer instance to send messages
	 */
	protected MailerSwift $mailer;

	/**
	 * Constructor.
	 * 
	 * @param string $subject Message subject
	 * @param string|null $body (optional) Message body
	 */
	public function __construct(string $subject, string $body = null)
	{
		$this->message = (new Swift_Message)
			->setSubject($subject)
			->setBody($body)
			->setContentType('text/html');
	}

	/**
	 * @inheritDoc
	 */
	public function via(MailerFacadeInterface $mailer): self
	{
		$this->mailer = $mailer;
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function render(string $template, array $data = null): self
	{
		$body = $this->mailer->getView()->render($template, $data);
		$this->message->setBody($body);
		return $this;
	}

	/**
	 * Attach file(s) to message.
	 * 
	 * @param string|string[]|<string, string> $file Path(s) to file(s).
	 * If parameter an array it can be:
	 * - list, such as ['pathToFile1', 'pathToFile2', ...]
	 * - associative or mixed, such as ['pathToFile1' => 'fileName1', 'pathToFile2', ...]
	 * 
	 * @param string|null $fileName (optional) File name of attachment. Does matter if `$file` argument is string.
	 * @param string|null $contentType (optional) Mime content type.
	 * 
	 * @return self
	 */
	public function attach(string|array $file, string $fileName = null, string $contentType = null): self
	{
		if (is_array($file)) {
			foreach ($file as $k => $v) {
				if (is_string($k)) {
					$path = $k;
					$name = $v;
				} else {
					$path = $v;
					$name = null;
				}

				$this->message->attach($this->createAttachment($path, $name, $contentType));
			}
		} else {
			$this->message->attach($this->createAttachment($file, $fileName, $contentType));
		}

		return $this;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * Get `Swift_Message` message object in this implementation
	 * 
	 * @return Swift_Message
	 */
	public function getBehind(): Swift_Message
	{
		return $this->message;
	}

	/**
	 * {@inheritDoc}
	 */
	public function send(): int
	{
		if (!isset($this->mailer)) {
			throw new InvalidConfigException("Can't send message, mailer not defined.");
		}
		return $this->mailer->send($this);
	}

	/**
	 * {@inheritDoc}
	 */
	public function to(string|array $addresses, string $name = null): self
	{
		if (is_array($addresses)) {
			$this->message->setTo($addresses, $name);
		} else {
			$to = $this->extractAddresses($addresses);
			foreach ($to as $address) {
				$this->message->addTo($address, $name);
			}
		}

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setCharset(string $charset): self
	{
		$this->message->setCharset($charset);
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFrom(): string|array|null
	{
		return $this->message->getFrom();
	}

	/**
	 * {@inheritDoc}
	 */
	public function setFrom(string|array $addresses, $name = null): self
	{
		$this->message->setFrom($addresses, $name);
		return $this;
	}

	/**
	 * Filter addresses string.
	 * 
	 * @param string $address
	 * 
	 * @return array Array of addresses
	 */
	protected function extractAddresses(string $address): array
	{
		// Remove whitespaces
		$to = preg_replace('/\s+/', '', $address);
		$to = str_replace(';', ',', $to);
		$to = explode(',', $to);
		// Remove empty values
		$to = array_filter($to);
		// Work with addresses like: 'Somename<name@domain.tld>' and cut address between <> braces
		$to = array_map(fn ($item) => preg_replace('/^.*<(.+)>.*/', '${1}', $item), $to);
		return $to;
	}

	protected function createAttachment(string $path, string $name = null, string $contentType = null): Swift_Attachment
	{
		$attachment = Swift_Attachment::fromPath($path, $contentType);
		if ($name !== null) {
			$attachment->setFilename($name);
		}
		return $attachment;
	}
}