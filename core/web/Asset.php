<?php

declare(strict_types=1);

namespace core\web;

class Asset
{
	/**
	 * @var string
	 */
	public string $path;

	/**
	 * @var array
	 */
	public array $options;

	/**
	 * Constructor.
	 * 
	 * @param string $path
	 * @param array $options
	 */
	public function __construct(string $path, array $options = [])
	{
		$this->path = $path;
		$this->options = $options;
	}
}