<?php

declare(strict_types=1);

namespace core\exception;

/**
 * HttpException represents an exception caused by invalid operations of end-users.
 *
 * The HTTP error code can be obtained via `getHttpStatus()`.
 * Error handlers may use this status code to decide how to format the error page.
 * 
 * @see https://httpstatuses.com/
 */
class HttpException extends BaseException
{
	/**
	 * @var int HTTP status code, such as 403, 404, 500, etc.
	 */
	private $httpStatusCode;

	/**
	 * Constructor.
	 * @param int $status HTTP status code, such as 404, 500, etc.
	 * @param string $message error message
	 * @param int $code error code
	 * @param \Exception|null $previous The previous exception used for the exception chaining.
	 */
	public function __construct(int $status, string $message = null, int $code = 0, \Exception $previous = null)
	{
		$this->httpStatusCode = $status;
		parent::__construct($message, $code, $previous);
	}

	public function getHttpStatus(): int
	{
		return $this->httpStatusCode;
	}

	public function __toString()
	{
		return __CLASS__ . ": [{$this->httpStatusCode}]: {$this->message}\n";
	}

	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
		return 'HTTP error';
	}
}
