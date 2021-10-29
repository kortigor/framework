<?php

declare(strict_types=1);

namespace core\mail;

use Closure;

/**
 * Mailer config
 */
class Config
{
	/**
	 * Constructor.
	 *
	 * @param string $host Smtp host name
	 * @param int $port = 465 Smtp host port
	 * @param string $encryption Encryption type
	 * @param string $login Smtp host login
	 * @param string $password Smtp host password
	 * @param string $charset Charset
	 * @param string|Closure $from Default 'from' i.e. 'John Smith'
	 * @param string|Closure $email Default from email i.e. 'smith@domain.com'
	 * @param int $timeout (optional) Smtp host connection timeout, default 30s.
	 * @param string $layout (optional) View layout to render html messages
	 */
	public function __construct(
		public string $host,
		public int $port,
		public string $encryption,
		public string $login,
		public string $password,
		public string $charset,
		public string|Closure $from,
		public string|Closure $email,
		public int $timeout = 30,
		public string $layout = '',
	) {
		if ($from instanceof Closure) {
			$this->from = (string) $from();
		}

		if ($email instanceof Closure) {
			$this->email = (string) $email();
		}
	}
}