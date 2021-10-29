<?php

declare(strict_types=1);

namespace core\web;

use core\data\SessionStorage as BaseSessionStorage;
use core\helpers\ArrayHelper;

/**
 * Class represent improved implementation of session storage.
 */
class SessionStorage extends BaseSessionStorage
{
	/**
	 * Read storage data.
	 * 
	 * @param string $key Key name of the storage element. The key may be specified
	 * in a dot format to retrieve the value of a sub-array or the property of an embedded object.
	 * In particular, if the key is `x.y.z`, then the returned value would be `$array['x']['y']['z']`
	 * @param null $default Default value to return if key not exists.
	 * 
	 * @return mixed Data or default if key not exists.
	 * @see ArrayHelper::getValue
	 */
	public function read(string $key = null, $default = null): mixed
	{
		$this->session_start();
		if ($key === null) {
			$result = $_SESSION[$this->name];
		} else {
			$result = ArrayHelper::getValue($_SESSION[$this->name], $key, $default);
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
		$result = ArrayHelper::remove($_SESSION[$this->name], $key, $default);
		session_write_close();

		return $result;
	}

	/**
	 * Check for exsists data.
	 * 
	 * @param string $key Key name of the storage element. The key may be specified
	 * in a dot format to retrieve the value of a sub-array or the property of an embedded object.
	 * In particular, if the key is `x.y.z`, then the returned value would be `$array['x']['y']['z']`
	 * 
	 * @return bool true if key value exists.
	 * @see ArrayHelper::getValue
	 */
	public function has(string $key): bool
	{
		$markerHasNo = hash('sha256', strval(mt_rand(0, 99999)));
		$this->session_start();
		$value = ArrayHelper::getValue($_SESSION[$this->name], $key, $markerHasNo);
		session_write_close();

		return $value !== $markerHasNo;
	}
}