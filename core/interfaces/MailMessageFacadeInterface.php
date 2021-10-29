<?php

declare(strict_types=1);

namespace core\interfaces;

/**
 * Simple mail message facade interface.
 */
interface MailMessageFacadeInterface
{
	/**
	 * Send message.
	 * 
	 * @return int The number of successful recipients. Can be 0 which indicates failure.
	 */
	public function send(): int;

	/**
	 * Set mailer to sent message.
	 * 
	 * @return self
	 */
	public function via(MailerFacadeInterface $mailer): self;

	/**
	 * Render and set message body.
	 * 
	 * @param string $template Template to render.
	 * @param array|null $data Data to pass into template.
	 * 
	 * @return self
	 */
	public function render(string $template, array $data = null): self;

	/**
	 * Set the 'to' addresses of this message.
	 *
	 * If multiple recipients will receive the message an array should be used.
	 * Example: ['receiver@domain.org', 'other@domain.org' => 'Name']
	 *
	 * If `$name` is passed and the `$address` parameter is a string,
	 * this name will be associated with the address.
	 * 
	 * If `$address` parameter is a string,
	 * it can contain several addresses comma or semicolon separated
	 * Examlpe: 'addr1@domain.tld,addr2@domain.tld;addr3@domain.tld;'
	 *
	 * @param string|array $addresses
	 * @param string|null $name
	 *
	 * @return self
	 */
	public function to(string|array $addresses, string $name = null): self;

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
	public function attach(string|array $file, string $fileName = null, string $contentType = null): self;

	/**
	 * Set the character set of this message.
	 * 
	 * @param string $charset
	 * 
	 * @return self
	 */
	public function setCharset(string $charset): self;

	/**
	 * Set the from address of this message.
	 *
	 * You may pass an array of addresses if this message is from multiple people.
	 *
	 * If $name is passed and the first parameter is a string, this name will be
	 * associated with the address.
	 * 
	 * @param string|array $addresses
	 * @param string $name
	 * 
	 * @return self
	 */
	public function setFrom(string|array $addresses, string $name): self;

	/**
	 * Get the from address of this message.
	 * 
	 * @return string|array|null
	 */
	public function getFrom(): string|array|null;

	/**
	 * Get message object behind facade.
	 * 
	 * @return object
	 */
	public function getBehind(): object;
}