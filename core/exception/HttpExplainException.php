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
class HttpExplainException extends HttpException
{
	/**
	 * Constructor.
	 * @param int $status HTTP status code, such as 404, 500, etc.
	 * @param string $message error message
	 * @param int $code error code
	 * @param \Exception|null $previous The previous exception used for the exception chaining.
	 */
	public function __construct(int $status, string $message = null, int $code = 0, \Exception $previous = null)
	{
		parent::__construct($status, $message, $code, $previous);
	}

	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
		return 'HTTP error with explain';
	}
}
