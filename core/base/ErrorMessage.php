<?php

declare(strict_types=1);

namespace core\base;

use JsonSerializable;

/**
 * Simple error message implementation.
 */
class ErrorMessage implements JsonSerializable
{
	/**
	 * @var bool
	 */
	private bool $error = true;

	/**
	 * @var array
	 */
	private array $messages = [];

	/**
	 * Constructor.
	 * 
	 * @param string $type Message type.
	 * @param string|null $message Message text.
	 */
	public function __construct(private string $type, string $message = null)
	{
		if ($message !== null) {
			$this->addMessage($message);
		}
	}

	/**
	 * Add message.
	 * 
	 * @param string $message Message text.
	 * 
	 * @return self
	 */
	public function addMessage(string $message): self
	{
		$this->messages[] = trim($message);
		return $this;
	}

	/**
	 * Implementation of JsonSerializable
	 * 
	 * @return mixed
	 */
	public function jsonSerialize()
	{
		return [
			'error' => $this->error,
			'type' => $this->type,
			'message' => implode(', ', $this->messages),
		];
	}
}