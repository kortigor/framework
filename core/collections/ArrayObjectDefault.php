<?php

declare(strict_types=1);

namespace core\collections;

use ArrayObject;
use ArrayIterator;

class ArrayObjectDefault extends ArrayObject
{
	/**
	 * @var mixed
	 */
	protected $default;

	/**
	 * @var array
	 */
	protected array $array = [];

	/**
	 * Constructor.
	 * 
	 * @param array $array 
	 * @param null $default Default value for elements that not exists.
	 */
	public function __construct(array $array = [], $default = null)
	{
		$this->setDefault($default);

		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$this->array[$key] = new self($value);
			} else {
				$this->array[$key] = $value;
			}
		}
	}

	/**
	 * Set default value for elements that not exists
	 * 
	 * @param mixed $default
	 * 
	 * @return self
	 */
	public function setDefault($default): self
	{
		$this->default = $default;
		return $this;
	}

	/**
	 * Get default value
	 * 
	 * @return mixed
	 */
	public function getDefault()
	{
		return $this->default;
	}

	public function __get($key)
	{
		return $this->offsetGet($key);
	}

	/**
	 * Equivalent of array_key_exists()
	 * @param mixed $key
	 * 
	 * @return bool
	 */
	public function array_key_exists($key): bool
	{
		return $this->offsetExists($key);
	}

	/**
	 * Equivalent of in_array()
	 * 
	 * @param mixed $value
	 * 
	 * @return bool
	 */
	public function in_array($value): bool
	{
		return in_array($value, $this->array);
	}

	/**
	 * Get object as array
	 * 
	 * @return array
	 */
	public function asArray(): array
	{
		return $this->array;
	}

	public function offsetGet($key)
	{
		if (array_key_exists($key, $this->array)) {
			return $this->array[$key];
		} else {
			return $this->getDefault();
		}
	}

	public function offsetSet($key, $value)
	{
		if ($key) {
			$this->array[$key] = $value;
		} else {
			$this->array[] = $value;
		}
	}

	public function offsetUnset($key)
	{
		if (array_key_exists($key, $this->array)) {
			unset($this->array[$key]);
		}
	}

	public function offsetExists($key)
	{
		return array_key_exists($key, $this->array);
	}

	public function append($value)
	{
		$this->array[] = $value;
	}

	public function count()
	{
		return count($this->array);
	}

	public function getIterator()
	{
		return new ArrayIterator($this->array);
	}

	public function __toString()
	{
		return 'Array';
	}
}