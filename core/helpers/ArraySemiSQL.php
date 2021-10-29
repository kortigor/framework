<?php

declare(strict_types=1);

namespace core\helpers;

/**
 * Class for working with multi-line arrays of the same structure data.
 */
class ArraySemiSQL
{
	/**
	 * Constructor.
	 * 
	 * @param array $data Array of arrays like:
	 * ```
	 * [
	 * 		[key=>value, key2=>value2, ...],
	 * 		[key=>value3, key2=>value4, ...],
	 * 		[key=>value, key2=>value8, ...]
	 * 		...
	 * ]
	 * ```
	 * 
	 * @return void
	 */
	public function __construct(private array $data = [])
	{
	}

	/**
	 * In a multi-line associative array, select the lines in which the specified key corresponds to the desired value.
	 * Index keys are preserved.
	 * 
	 * @param string $k The name of the key (field) by which the check is performed, for example 'key2'
	 * @param mixed $v The value of the field, to be included in the selection
	 * @param bool $strict Strict compare or not
	 * 
	 * @return array An array containing matching strings
	 */
	public function whereByField(string &$k, mixed &$v, bool $strict = true): array
	{
		if (empty($this->data)) {
			return [];
		}

		return array_filter(
			$this->data,
			fn ($line) => $strict === true ? $line[$k] === $v : $line[$k] == $v
		);
	}

	/**
	 * List of all unique values for the specified field
	 * 
	 * @param string $k
	 * 
	 * @return array
	 */
	public function fieldValuesList(string $k): array
	{
		$out = [];
		if (empty($this->data)) {
			return $out;
		}

		foreach ($this->data as $line) {
			if (isset($line[$k])) {
				$out[] = $line[$k];
			}
		}

		if (empty($out)) {
			return $out;
		}

		$out = array_unique($out);
		return $this->reindex($out);
	}

	/**
	 * Re-indexes an array by creating keys in ascending order
	 * 
	 * ```
	 * [
	 * 		[2] => [title => Section],
	 * 		[1] => [title => Sub-Section],
	 * 		[5] => [title => Sub-Sub-Section],
	 * ];
	 * ```
	 * ```
	 * [
	 * 		[0] => [title => Section],
	 * 		[1] => [title => Sub-Section],
	 * 		[2] => [title => Sub-Sub-Section],
	 * ];
	 * ```
	 * 
	 * @param array $arr
	 * 
	 * @return array
	 */
	public function reindex(array $arr): array
	{
		return array_combine(range(0, count($arr) - 1), array_values($arr));
	}
}