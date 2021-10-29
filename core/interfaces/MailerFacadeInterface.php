<?php

declare(strict_types=1);

namespace core\interfaces;

/**
 * Simple facade mailer interface
 */
interface MailerFacadeInterface
{
	/**
	 * Send message.
	 * 
	 * @param MailMessageFacadeInterface $message Message to send.
	 * 
	 * @return int The number of successful recipients. Can be 0 which indicates failure.
	 */
	public function send(MailMessageFacadeInterface $message): int;

	/**
	 * Set from name.
	 * 
	 * @param string $name Name to set.
	 * 
	 * @return self
	 */
	public function setFrom(string $name): self;

	/**
	 * Set from email address.
	 * 
	 * @param string $address Email address to set.
	 * 
	 * @return self
	 */
	public function setEmail(string $address): self;

	/**
	 * Get from name.
	 * 
	 * @return string|null Name or null if not set
	 */
	public function getFrom(): ?string;

	/**
	 * Get from email address.
	 * 
	 * @return string|null Email address or null if not set
	 */
	public function getEmail(): ?string;

	/**
	 * Get mailer object behind facade.
	 * 
	 * @return object
	 */
	public function getBehind(): object;
}