<?php

declare(strict_types=1);

namespace core\exception;

/**
 * CartItemNotFoundException represents an exception caused by item ih the cart not exists or inactive.
 */
class CartItemNotFoundException extends BaseException
{
	/**
	 * @var string Cart item id caused exception
	 */
	private string $id;

	/**
	 * Constructor.
	 * 
	 * @param string $id Cart item id caused exception
	 * @param string $message error message
	 * @param int $code error code
	 * @param \Exception|null $previous The previous exception used for the exception chaining.
	 */
	public function __construct(string $id, string $message = null, int $code = 0, \Exception $previous = null)
	{
		$this->id = $id;
		parent::__construct($message, $code, $previous);
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function __toString()
	{
		return __CLASS__ . ": [{$this->id}]: {$this->message}\n";
	}

	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
		return 'Cart item error';
	}
}