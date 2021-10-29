<?php

declare(strict_types=1);

namespace core\orm;

/**
 * ORM config
 */
class Config
{
	/**
	 * Constructor
	 *
	 * @param string $driver DB driver
	 * @param string $host DB host
	 * @param string $database DB name
	 * @param string $prefix DB prefix
	 * @param string $username Username
	 * @param string $password Passwors
	 * @param string $charset DB charset
	 * @param string $collation DB collation
	 *
	 * @return void
	 */
	public function __construct(
		public string $driver,
		public string $host,
		public string $database,
		public string $prefix,
		public string $username,
		public string $password,
		public string $charset,
		public string $collation,
	) {
		PageResolver::byGetQueryParameter('page');
	}
}