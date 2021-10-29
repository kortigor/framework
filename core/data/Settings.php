<?php

declare(strict_types=1);

namespace core\data;

use core\base\Singleton;
use core\interfaces\ConfigProviderInterface;

final class Settings extends Singleton
{
	/**
	 * @var array Settings storage
	 */
	private array $settings = [];

	/**
	 * Setting value magic getter
	 * 
	 * @param string $name
	 * 
	 * @return mixed
	 */
	public function __get(string $name)
	{
		return $this->settings[$name] ?? null;
	}

	/**
	 * Isset value magic method
	 * 
	 * @param string $name
	 * 
	 * @return mixed
	 */
	public function __isset(string $name)
	{
		return isset($this->settings[$name]);
	}

	/**
	 * Unset value magic method
	 * 
	 * @param string $name
	 * 
	 * @return mixed
	 */
	public function __unset(string $name)
	{
		unset($this->settings[$name]);
	}

	/**
	 * Set key value data
	 * 
	 * @param string $name
	 * @param mixed $value
	 * 
	 * @return self
	 */
	public function set(string $name, mixed $value): self
	{
		$this->settings[$name] = $value;
		return $this;
	}

	/**
	 * Remove data from the key
	 * 
	 * @param string $name Key name
	 * 
	 * @return self
	 */
	public function remove(string $name): self
	{
		unset($this->settings[$name]);
		return $this;
	}

	/**
	 * Add data to existing key or set if the key does not exists.
	 * 
	 * @param string $name
	 * @param mixed $value
	 * 
	 * @return self
	 */
	public function add(string $name, mixed $value): self
	{
		if (is_array($this->settings[$name] ?? false)) {
			$this->settings[$name] = array_merge(
				$this->settings[$name],
				is_array($value) ? $value : [$value]
			);
		} else {
			$this->set($name, $value);
		}

		return $this;
	}

	/**
	 * Import config file.
	 * 
	 * Config file should return array or variable definition.
	 * 
	 * Examples:
	 * ```
	 * return [
	 * 	'key1' => 'value1',
	 * 	'key2' => 'value2',
	 *  ...
	 * ];
	 * ```
	 * OR
	 * ```
	 * return $var;
	 * ```
	 * 
	 * @param string $file
	 * 
	 * @return bool True on import success
	 */
	public function importFile(string $file): bool
	{
		if (!is_file($file)) {
			return false;
		}

		$config = require_once $file;
		$name = pathinfo($file, PATHINFO_FILENAME);
		$this->set($name, $config);
		return true;
	}

	/**
	 * Add data provided by ConfigProviderInterface
	 * 
	 * @param string $name
	 * @param ConfigProviderInterface $config
	 * 
	 * @return self
	 */
	public function addConfig(string $name, ConfigProviderInterface $config): self
	{
		$this->add($name, $config->toArray());
		return $this;
	}
}