<?php

declare(strict_types=1);

namespace core\data;

use InvalidArgumentException;
use RuntimeException;

/**
 * Base implementation of session storage.
 */
class SessionStorage
{
	/**
	 * @var array Default session config.
	 * @see https://www.php.net/manual/ru/session.configuration.php
	 */
	public static array $defaultOptions = [
		'cookie_lifetime' => 3600,
		'gc_maxlifetime' => 3600
	];

	/**
	 * @var array
	 */
	protected array $options;

	/**
	 * @var bool Indicates freshly initialized storage.
	 */
	protected bool $wasInitialized = false;

	/**
	 * Constructor.
	 * 
	 * @param string $name Storage name.
	 * @param array $options Session storage options to pass to `session_start()`.
	 * @see https://www.php.net/manual/ru/function.session-start.php
	 * @see https://www.php.net/manual/ru/session.configuration.php
	 */
	public function __construct(protected string $name, array $options = [])
	{
		if (empty($name)) {
			throw new InvalidArgumentException('Session storage name MUST be not empty');
		}
		$this->options = array_merge(static::$defaultOptions, $options);

		$this->session_start();
		if (!session_id()) {
			throw new RuntimeException('Unable to start session');
		}

		if (!isset($_SESSION, $_SESSION[$this->name])) {
			$_SESSION[$this->name] = [];
			$this->wasInitialized = true;
		}
		session_write_close();
	}

	/**
	 * Is storage was initialized when class instantiated?
	 * 
	 * It indicates that session storage in `$_SESSION[$name]`
	 * was not exists before instantiation of this class.
	 * 
	 * @return bool true if was initialized
	 */
	public function wasInitialized(): bool
	{
		return $this->wasInitialized;
	}

	/**
	 * Check storage empty.
	 * 
	 * @return bool
	 */
	public function empty(): bool
	{
		return empty($_SESSION[$this->name]);
	}

	/**
	 * Get storage name.
	 * 
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Get storage's session id.
	 * 
	 * @return string
	 */
	public function getId(): string
	{
		$this->session_start();
		$id = session_id();
		session_write_close();

		return $id;
	}

	/**
	 * Save data into storage.
	 * 
	 * @param string $key Storage key to write
	 * @param mixed $data Data to write
	 * 
	 * @return self this instance for chaining
	 */
	public function write(string $key, $data): self
	{
		$this->session_start();
		$_SESSION[$this->name][$key] = $data;
		session_write_close();

		return $this;
	}

	/**
	 * Save array to session. New data keys - added, existing - renewed
	 * 
	 * @param string $name Storage key to write
	 * @param array $data Array data to write
	 * 
	 * @return self this instance for chaining
	 */
	public function writeArray(string $key, array $data = []): self
	{
		$this->session_start();
		foreach ($data as $ind => $value) {
			if (is_array($value)) {
				session_write_close();
				$this->writeArray($ind, $value);
			} else {
				$_SESSION[$this->name][$key][$ind] = $value;
			}
		}
		session_write_close();

		return $this;
	}

	/**
	 * Remove storage data with key.
	 * 
	 * @param string $key Storage key to remove.
	 * 
	 * @return self this instance for chaining
	 */
	public function remove(string $key): self
	{
		$this->session_start();
		unset($_SESSION[$this->name][$key]);
		session_write_close();

		return $this;
	}

	/**
	 * Read storage data.
	 * 
	 * @param string $key Key name of the storage element.
	 * @param null $default Default value to return if key not exists.
	 * 
	 * @return mixed Data or default if key not exists.
	 */
	public function read(string $key = null, $default = null): mixed
	{
		$this->session_start();
		if ($key === null) {
			$result = $_SESSION[$this->name];
		} else {
			$result = $_SESSION[$this->name][$key] ?? $default;
		}
		session_write_close();

		return $result;
	}

	/**
	 * Read and unset (like shift stack) storage data.
	 * 
	 * @param string $key Storage key to shift.
	 * 
	 * @return mixed Data or default if key not exists.
	 */
	public function shift(string $key, $default = null): mixed
	{
		$this->session_start();
		$result = $_SESSION[$this->name][$key] ?? $default;
		$this->remove($key);
		session_write_close();

		return $result;
	}

	/**
	 * Check for exsists data.
	 * 
	 * @param string $key Key name of the storage element.
	 * 
	 * @return bool true if key value exists.
	 */
	public function has(string $key): bool
	{
		$this->session_start();
		$result = isset($_SESSION[$this->name][$key]);
		session_write_close();

		return $result;
	}

	/**
	 * Clear all storage data.
	 * 
	 * @return self this instance for chaining
	 */
	public function clear(): self
	{
		$this->session_start();
		$_SESSION[$this->name] = [];
		session_write_close();
		$this->wasInitialized = true;

		return $this;
	}

	/**
	 * Starts session with storage configuration.
	 * 
	 * @return void
	 */
	protected function session_start(): void
	{
		session_start($this->options);
	}
}