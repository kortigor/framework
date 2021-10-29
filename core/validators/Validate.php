<?php

declare(strict_types=1);

namespace core\validators;

/**
 * Validator class.
 *
 * @copyright  2007 Berezin Evgeniy.
 * @author Kort Igor <kort.igor@gmail.com> Powered and extended.
 */
class Validate
{
	/**
	 * Remove slashes.
	 * 
	 * @param string|array $data
	 * 
	 * @return void
	 */
	public function removeSlashes(string|array &$data): void
	{
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$this->removeSlashes($data[$key]);
			} elseif (is_string($value)) {
				$data[$key] = stripslashes($value);
			}
		}
	}

	/**
	 * Check for variable type
	 *
	 * @param string $type Name of checking type
	 * @param mixed $value Checking variable
	 * @return bool
	 */
	protected function byType(string $type, &$value): bool
	{
		switch ($type) {
			case 'int':
				if (is_int($value)) return true;
				break;
			case 'numeric':
				if (is_numeric($value)) return true;
				break;
			case 'string':
				if (is_string($value)) return true;
				break;
			case 'array':
				if (is_array($value)) return true;
				break;
			case 'bool':
				if (is_bool($value)) return true;
				if ($this->byRegular('/^[01]{1}$/iu', $value)) return true;
				break;
		}
		return false;
	}

	/**
	 * Check for string or int variable size
	 *
	 * @param int $minSize Min count of symbols in variable
	 * @param int $maxSize Max count of symbols in variable
	 * @param string|int $value Checking variable
	 * @return bool
	 */
	protected function bySize(int $minSize, int $maxSize, &$value): bool
	{
		if ($minSize === 0 && $maxSize === 0) return true;
		if (mb_strlen($value, 'UTF-8') < $minSize) return false;
		if ($maxSize > 0 && mb_strlen($value, 'UTF-8') > $maxSize) return false;
		return true;
	}

	/**
	 * Check with regular expression
	 *
	 * @param string $regular Regular string
	 * @param string $value Checking value
	 * @return bool
	 */
	protected function byRegular($regular, &$value): bool
	{
		return preg_match($regular, $value);
	}

	/**
	 * Check image type without mime_content_type
	 *
	 * @param string $file Filename
	 * @return string|bool Picture type or false
	 */
	public function imageType(string $file): string|bool
	{
		$fl = @fopen($file, 'r');
		if ($fl) {
			$data = fread($fl, 4);
			fclose($fl);
			if (substr($data, 0, 2) == "\xff\xd8") return 'image/jpeg';
			if (substr($data, 0, 4) == "GIF8") return 'image/gif';
			if (substr($data, 0, 4) == "\x89PNG" || substr($data, 0, 3) == "PNG") return 'image/x-png';
		}

		return false;
	}

	/**
	 * Check for string format
	 *
	 * @param string $value Checking value
	 * @return bool
	 */
	public function string(&$value, $minSize = 1, $maxSize = 255): bool
	{
		if (!$this->byType('string', $value)) return false;
		if (!$this->bySize($minSize, $maxSize, $value)) return false;
		return true;
	}

	/**
	 * Check for string minimum length
	 *
	 * @param string $value Checking value
	 * @return bool
	 */
	public function minLength(&$value, $minSize = 1): bool
	{
		if (!$this->byType('string', $value)) return false;
		if (!$this->bySize($minSize, 0, $value)) return false;
		return true;
	}

	/**
	 * Check for string maximum length
	 *
	 * @param string $value Checking value
	 * @return bool
	 */
	public function maxLength(&$value, $maxSize = 1): bool
	{
		if (!$this->byType('string', $value)) return false;
		if (!$this->bySize(0, $maxSize, $value)) return false;
		return true;
	}

	/**
	 * Check for string value.
	 * Added by Kort
	 *
	 * @param string $value Checking value
	 * @param string $sample Checking values value )))
	 * @return bool
	 */
	public function stringEqual(&$value, $sample): bool
	{
		if (!$this->byType('string', $value)) return false;
		if ($value != $sample) return false;
		return true;
	}

	/**
	 * Check for value equal to sample.
	 * Added by Kort
	 * 
	 * @param mixed $value
	 * @param mixed $sample
	 * 
	 * @return bool
	 */
	public function equal(&$value, $sample): bool
	{
		if ($value !== $sample) return false;
		return true;
	}

	/**
	 * Check for "required" value.
	 * 
	 * `string` type: length greater than 0
	 * `array` type: not empty
	 * 
	 * @param mixed $value
	 * 
	 * @return bool
	 */
	public function required(&$value): bool
	{
		if ($this->string($value, 1, 0)) return true;
		if ($this->array($value) && !empty($value)) return true;
		return false;
	}

	/**
	 * Check for string format used as password.
	 * 
	 * String length should be more than 3 and not greater than 255
	 *
	 * @param string $value Checking value
	 * @return bool
	 */
	public function password(&$value): bool
	{
		if (!$this->byType('string', $value)) return false;
		if (!$this->bySize(4, 255, $value)) return false;
		return true;
	}

	/**
	 * Check for array format
	 *
	 * @param array $value Checking value
	 * @return bool
	 */
	public function array(&$value): bool
	{
		if (!$this->byType('array', $value)) return false;
		return true;
	}

	/**
	 * Check for boolean type
	 *
	 * @param array $value Checking value
	 * @return bool
	 */
	public function boolean(&$value): bool
	{
		if (!$this->byType('bool', $value)) return false;
		return true;
	}

	/**
	 * Check for full(unlimited length) text format
	 *
	 * @param string $value Checking value
	 * @return bool
	 */
	public function text(&$value): bool
	{
		if (!$this->byType('string', $value)) return false;
		if (!$this->bySize(1, 0, $value)) return false;
		return true;
	}

	/**
	 * Check for identificator
	 *
	 * @param int $value Checking value
	 * @return bool
	 */
	public function id(&$value): bool
	{
		$regular = '/^[1-9][0-9]*$/';
		if (!$this->byType('int', $value)) return false;
		if (!$this->bySize(1, 10, $value)) return false;
		if (!$this->byRegular($regular, $value)) return false;
		if ($value == 0) return false;
		return true;
	}

	/**
	 * Check for non zero digit
	 *
	 * @param int $value Checking value
	 * @param int $minSize
	 * @param int $maxSize, 0 - does mean unlimited
	 * @param string $regular
	 * @return bool
	 */
	public function nonZeroNumeric(&$value, $minSize = 1, $maxSize = 10, $regular = '/^[1-9]\d*$/'): bool
	{
		if (!$this->byType('int', $value)) return false;
		if (!$this->bySize($minSize, $maxSize, $value)) return false;
		if (!$this->byRegular($regular, $value)) return false;
		return true;
	}

	/**
	 * Check for numeric
	 *
	 * @param int $value Checking value
	 * @return bool
	 */
	public function numeric(&$value, $minLength = 1, $maxLength = 255): bool
	{
		if (!$this->byType('numeric', $value)) return false;
		if (!$this->bySize($minLength, $maxLength, $value)) return false;
		return true;
	}

	/**
	 * Check for vote value
	 *
	 * @param int $value Checking value
	 * @return bool
	 */
	public function votes(&$value): bool
	{
		if (!$this->byType('int', $value)) return false;
		if (!$this->bySize(1, 1, $value)) return false;
		if ($value > 5 || $value < 1) return false;
		return true;
	}

	/**
	 * Check for e-mail format
	 *
	 * @param string $value Checking value
	 * @return bool
	 */
	public function email(&$value): bool
	{
		if (!$this->byType('string', $value)) return false;
		if (!$this->bySize(5, 50, $value)) return false;
		$regular = '/^(([a-zA-Z0-9]+[_\.\-]?[a-zA-Z0-9]*)+@([a-zA-Z0-9]+[_\.\-]?[a-zA-Z0-9]?)+\.[a-z]{2,4}){0,1}(&nbsp;| )*$/';
		if (!$this->byRegular($regular, $value)) return false;
		return true;
	}

	/**
	 * Check for phone number format
	 *
	 * @param string $value Checking value
	 * @return bool
	 */
	public function phone(&$value): bool
	{
		if (!$this->byType('string', $value)) return false;
		$probelStart = '(\(| \(?|\xA0\(?|\x|–\(?|—\(?|-\(?)';
		$probelEnd = '(\)|\)? |\)?\xA0|\)?–|\)?—|\)?-)';
		$probel = '( |\xA0|–|—|-)';
		$regular = '/^(\+\d{1,3}|8)' . $probelStart . '\d{1,5}' . $probelEnd . '\d' . $probel . '?(\d' . $probel . '?){3,}\d( |\xA0|&nbsp;|&#160;)*$/ui';
		if (!$this->byRegular($regular, $value)) return false;
		return true;
	}

	/**
	 * Check for cellphone number format
	 *
	 * @param string $value Checking value
	 * @return bool
	 */
	public function cellphone(&$value): bool
	{
		if (!$this->byType('string', $value)) return false;
		$probelStart = '(\(| \(?|\xA0\(?|\x|–\(?|—\(?|-\(?)';
		$probelEnd = '(\)|\)? |\)?\xA0|\)?–|\)?—|\)?-)';
		$probel = '( |\xA0|–|—|-)';
		$regular = '/^(\+\d{1,3}|8)' . $probelStart . '\d{1,5}' . $probelEnd . '\d' . $probel . '?(\d' . $probel . '?){3,}\d( |\xA0|&nbsp;|&#160;)*$/ui';
		if (!$this->byRegular($regular, $value)) return false;
		return true;
	}

	/**
	 * Check for cellphone number format
	 *
	 * @param string $value Checking value
	 * @return bool
	 */
	public function cellphone2(&$value): bool
	{
		if (!$this->byType('string', $value)) return false;
		if (!$this->byRegular('/^(7|8)\d{10}$/ui', $value)) return false;
		return true;
	}

	/**
	 * Check for skype name format
	 *
	 * @param string $value Checking value
	 * @return bool
	 */
	public function skype(&$value): bool
	{
		if (!$this->byType('string', $value)) return false;
		if (!$this->byRegular('/^[a-zа-яё0-9\-\_][a-zа-яё0-9\-\_ \.]{2,30}(&nbsp;| )*$/ui', $value)) return false;
		return true;
	}

	/**
	 * Check for icq format
	 *
	 * @param string $value Checking value
	 * @return bool
	 */
	public function icq(&$value): bool
	{
		if (!$this->byType('string', $value)) return false;
		if (!$this->byRegular('/^[0-9][0-9\-—–]{1,10}[0-9](&nbsp;| )*$/u', $value)) return false;
		return true;
	}

	/**
	 * Check for login
	 *
	 * @param string $value Checking value
	 * @return bool
	 */
	public function login(&$value): bool
	{
		if (!$this->byType('string', $value)) return false;
		if (!$this->byRegular('/^[a-zа-яё0-9\-\ ]{3,30}$/ui', $value)) return false;
		return true;
	}

	/**
	 * Check for url
	 *
	 * @param string $value
	 * @return bool
	 */
	public function url(&$value): bool
	{
		if (!$this->byType('string', $value)) return false;
		if (!$this->byRegular('#^(http|https):{1}/{2}[\w\#$%&~/.\-;:=,?@\[\]\(\)]+$#siu', $value)) return false;
		// if (!$this->byRegular('/^(https?:\/\/)?(www\.)?[a-zа-яёЁ0-9\.\-_]{1,100}\.[a-zа-яёЁ]{2,4}\/?[a-zа-яёЁ0-9\:,\-_ \?\&\=\%\.#\/]*$/iu',$value)) return false;
		return true;
	}

	/**
	 * Check for alias
	 *
	 * @param string $value Checking value
	 * @param bool $enableSpaces Ignored spaces in alias
	 * @return bool
	 */
	public function alias(&$value, bool $enableSpaces = false): bool
	{
		if (!$this->byType('string', $value)) return false;
		if (!$this->byRegular('/^[a-z0-9\_\-' . (($enableSpaces) ? '\ ' : '') . ']{1,60}$/iu', $value)) return false;
		return true;
	}

	/**
	 * Check for rusalias
	 *
	 * @param string $value Checking value
	 * @param bool $enableSpaces Ignored spaces in alias
	 * @return bool
	 */
	public function rusalias(&$value, bool $enableSpaces = false): bool
	{
		if (!$this->byType('string', $value)) return false;
		if (!$this->byRegular('/^[a-zа-яё0-9\_\-' . (($enableSpaces) ? '\ ' : '') . ']{1,60}$/iu', $value)) return false;
		return true;
	}

	/**
	 * Check for date. Supports patterns:
	 * ```php
	 * 'dd-mm-yyyy'
	 * 'dd.mm.yyyy'
	 * 'dd/mm/yyyy'
	 * 'yyyy-mm-dd'
	 * 'yyyy.mm.dd'
	 * 'yyyy/mm/dd'
	 * ```
	 * @param mixed $value
	 * @param string $pattern
	 * 
	 * @return void
	 */
	public function date(&$value, $pattern = 'yyyy-mm-dd'): bool
	{
		if (!$this->byType('string', $value)) return false;

		$dd	= '((0[1-9])|([1-2][0-9])|(3[0-1]))';
		$mm	= '((0[1-9])|(1[0-2]))';
		$yyyy = '([1-2][0-9]{3})';

		switch ($pattern) {
			case 'dd-mm-yyyy':
				$regular = $dd . '\-' . $mm . '\-' . $yyyy;
				break;
			case 'dd.mm.yyyy':
				$regular = $dd . '\.' . $mm . '\.' . $yyyy;
				break;
			case 'dd/mm/yyyy':
				$regular = $dd . '\/' . $mm . '\/' . $yyyy;
				break;
			case 'yyyy-mm-dd':
				$regular = $yyyy . '\-' . $mm . '\-' . $dd;
				break;
			case 'yyyy.mm.dd':
				$regular = $yyyy . '\.' . $mm . '\.' . $dd;
				break;
			case 'yyyy/mm/dd':
				$regular = $yyyy . '\/' . $mm . '\/' . $dd;
				break;
			default:
				$regular = '';
		}
		if (!$this->byRegular('/^' . $regular . '$/', $value)) return false;
		return true;
	}

	/**
	 * Check valid datetime
	 * 
	 * @param mixed $value
	 * 
	 * @return bool
	 */
	public function datetime(&$value): bool
	{
		if (!$this->byType('string', $value)) return false;
		$regular = '/^((0[1-9])|([1-2][0-9])|(3[0-1]))\\-((0[1-9])|(1[0-2]))\\-([1-2][0-9]{3}) ([0-1][0-9]\:[0-5][0-9])|([2][0-3]\:[0-5][0-9])\:[0-5][0-9]$/';
		if (!$this->byRegular($regular, $value)) return false;
		return true;
	}

	/**
	 * Check datetime for ISO format
	 * 
	 * Supports all ISO formats, added by Kort
	 * 
	 * @param mixed $value
	 * @param string $format
	 * @return bool
	 * @see http://php.net/manual/ru/datetime.createfromformat.php
	 */
	public function datetimeIso(&$value, $format = 'Y-m-d'): bool
	{
		$d = \DateTime::createFromFormat($format, $value);
		return $d !== false && $d->format($format) === $value;
	}

	/**
	 * Check valid date from datepicker.
	 * 
	 * @param mixed $value
	 * 
	 * @return bool
	 */
	public function dateFromDatepicker(&$value): bool
	{
		if (!$this->byType('string', $value)) return false;
		if (!$this->byRegular('/^(([1-9])|([1-2][0-9])|(3[0-1])) [а-я]{3,8} ([1-2][0-9]{3})$/iu', $value)) return false;
		$months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
		$monthsNum = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
		$value = explode(' ', $value);
		$day = (int) $value[0];
		$month = (int) str_ireplace($months, $monthsNum, $value[1]);
		$year = (int) $value[2];
		return checkdate($month, $day, $year);
	}

	/**
	 * Manual checking method
	 *
	 * @param mixed $value
	 * @param string $type: 'int', 'numeric', 'string', 'array', 'bool'
	 * @param int $minSize
	 * @param int $maxSize, 0 - does mean unlimited
	 * @param string $regular
	 * @return bool
	 */
	public function check(&$value, string $type, int $minSize, int $maxSize, string $regular): bool
	{
		switch ($type) {
			case 'int':
				if (!$this->byType('int', $value)) return false;
				break;
			case 'numeric':
				if (!$this->byType('numeric', $value)) return false;
				break;
			case 'string':
				if (!$this->byType('string', $value)) return false;
				break;
			case 'array':
				if (!$this->byType('array', $value)) return false;
				break;
			case 'bool':
				if (!$this->byType('bool', $value)) return false;
				break;
			default:
				return false;
		}

		if (!$this->bySize($minSize, $maxSize, $value)) return false;
		if (!$this->byRegular($regular, $value)) return false;
		return true;
	}

	/**
	 * Check string for json, added by Kort
	 *
	 * @param string $value
	 * @return bool
	 */
	public function json(&$value): bool
	{
		json_decode($value);
		if (json_last_error() === JSON_ERROR_NONE) {
			return true;
		}
		return false;
	}

	/**
	 * Check string for HEX color like #CCCCCC, added by Kort
	 *
	 *	^              anchor for start of string
	 *	#              the literal #
	 *	(              start of group
	 *	?:             indicate a non-capturing group that doesn't generate backreferences
	 *	[0-9a-fA-F]    hexadecimal digit
	 *	{3}            three times
	 *	)              end of group
	 *	{1,2}          repeat either once or twice
	 *	$              anchor for end of string
	 *
	 *	This will match an arbitrary hexadecimal color value that can be used in CSS, such as #91bf4a or #f13.
	 *
	 *	Note: No support for RGBA hex color values, though.
	 *
	 * @param string $value
	 * @return bool
	 * @see https://stackoverflow.com/questions/1636350/how-to-identify-a-given-string-is-hex-color-format
	 */
	public function hexcolor(&$value): bool
	{
		$regular = '/^#(?:[0-9a-f]{3}){1,2}$/ui';
		if (!$this->byRegular($regular, $value)) {
			return false;
		}
		return true;
	}

	/**
	 * Check for serialized data.
	 * 
	 * added by Kort
	 *
	 * @param string $value (Required) Value to check to see if was serialized.
	 * @param bool $strict (Optional) Whether to be strict about the end of the string. Default value: true
	 * @return bool False if not serialized and true if it was
	 * @see https://developer.wordpress.org/reference/functions/is_serialized/
	 */
	function serialized(&$value, bool $strict = true): bool
	{
		// if it isn't a string, it isn't serialized.
		if (!is_string($value)) {
			return false;
		}
		$value = trim($value);
		if ('N;' == $value) {
			return true;
		}
		if (strlen($value) < 4) {
			return false;
		}
		if (':' !== $value[1]) {
			return false;
		}
		if ($strict) {
			$lastc = substr($value, -1);
			if (';' !== $lastc && '}' !== $lastc) {
				return false;
			}
		} else {
			$semicolon = strpos($value, ';');
			$brace = strpos($value, '}');
			// Either ; or } must exist.
			if (false === $semicolon && false === $brace) {
				return false;
			}
			// But neither must be in the first X characters.
			if (false !== $semicolon && $semicolon < 3) {
				return false;
			}
			if (false !== $brace && $brace < 4) {
				return false;
			}
		}
		$token = $value[0];
		switch ($token) {
			case 's':
				if ($strict) {
					if ('"' !== substr($value, -2, 1)) {
						return false;
					}
				} elseif (false === strpos($value, '"')) {
					return false;
				}
				// or else fall through
			case 'a':
			case 'O':
				return (bool) preg_match("/^{$token}:[0-9]+:/s", $value);
			case 'b':
			case 'i':
			case 'd':
				$end = $strict ? '$' : '';
				return (bool) preg_match("/^{$token}:[0-9.E-]+;$end/", $value);
		}
		return false;
	}
}