<?php

declare(strict_types=1);

/**
 * Calculate script start/end time
 * 
 * @return float
 */
function getMicrotime(): float
{
	list($usec, $sec) = explode(' ', microtime());
	return ((float)$usec + (float)$sec);
}

/**
 * Get object hash.
 * 
 * @param object $obj Object to get his hash
 * @param string $algo Hashing alrorytm
 * 
 * @return string Object hash
 */
function objectHash(object $obj, string $algo = 'md5'): string
{
	return hash($algo, serialize($obj));
}

/**
 * Parse variable as boolean
 * 
 * @param mixed $var
 * 
 * @return bool
 * @see filter_var()
 */
function filterBool($var): bool
{
	return filter_var($var, FILTER_VALIDATE_BOOLEAN);
}

/**
 * Sanitize string.
 * 
 * @param mixed $var
 * 
 * @return string
 * @see filter_var()
 */
function filterString($var): string
{
	return filter_var($var, FILTER_SANITIZE_STRING);
}

/**
 * Normalize value to decimal, i.e. 120 233,25 => 120233.25
 * 
 * Usable to store date in MySQL DECIMAL fields.
 * 
 * @param mixed $val
 * @param int $decimals
 * 
 * @return string Formatted value
 */
function formatDecimal(mixed $val, int $decimals = 2): string
{
	$val = str_replace(',', '.', (string) $val);
	$val = preg_replace('/[^0-9\-\.]/', '', $val);
	return number_format((float) $val, $decimals, '.', '');
}

/**
 * Case-insensitive realpath()
 * 
 * @param string $path
 * @return string|false
 */
function realpathi(string $path)
{
	$me = __METHOD__;

	$path = rtrim(preg_replace('#[/\\\\]+#', DS, $path), DS);
	$realPath = realpath($path);
	if ($realPath !== false) {
		return $realPath;
	}

	$dir = dirname($path);
	if ($dir === $path) {
		return false;
	}
	$dir = $me($dir);
	if ($dir === false) {
		return false;
	}

	$search = strtolower(basename($path));
	$pattern = '';
	for ($pos = 0; $pos < strlen($search); $pos++) {
		$pattern .= sprintf('[%s%s]', $search[$pos], strtoupper($search[$pos]));
	}
	return current(glob($dir . DS . $pattern));
}

/**
 * Get class or object short name without namespace.
 * 
 * @param object $objectOrClass Either a string containing the name of the class to explore, or an object.
 * 
 * @return string Class or object short name.
 * 
 * @throws ReflectionException If passed string and class name does not exists.
 */
function get_class_short(object|string $objectOrClass): string
{
	return (new ReflectionClass($objectOrClass))->getShortName();
}

/**
 * var_dump with convenient formatting
 *
 * @param mixed $value
 *
 * @return void
 */
function vardump($value, ...$values): void
{
	echo '<pre>';
	var_dump($value, ...$values);
	echo '</pre>';
}

/**
 * print_r with convenient formatting
 *
 * @param mixed $var
 *
 * @return void
 */
function printr($var): void
{
	echo '<pre>';
	print_r($var);
	echo '</pre>';
}

/**
 * Shortcut to get settings data.
 * 
 * @param string $param Path in dot format to config variable
 * @param mixed $default Default value if path is invalid.
 * 
 * @return mixed
 * @see \core\base\BaseApplication::c()
 */
function c(string $param, $default = null): mixed
{
	return Sys::$app->c($param, $default);
}

/**
 * Shortcut to translator.
 * 
 * @param string $message
 * @param string $textDomain
 * @param string|null $locale
 * 
 * @return string
 * @see \core\web\Application::i18n
 */
function t(string $message, string $textDomain = 'default', string $locale = null): string
{
	return Sys::$app->i18n?->translate($message, $textDomain, $locale) ?? $message;
}

/**
 * Shortcut to system formatter
 * 
 * @return \core\helpers\Formatter
 * @see \core\base\BaseApplication::formatter()
 */
function f(): \core\helpers\Formatter
{
	return Sys::$app->formatter;
}

/**
 * Get normalized filesystem full path.
 * 
 * @param string $path Path from site root.
 * 
 * @return string Full filesystem path.
 */
function fsPath(string $path): string
{
	$pathReal = realpath($_SERVER['DOCUMENT_ROOT'] . DS . $path);
	if ($pathReal === false) {
		return '';
	}

	return \core\helpers\FileHelper::normalizePath($pathReal);
}

/**
 * Shortcut to \core\helpers\FileHelper::normalizePath
 * 
 * @param string $path Path to normalize
 * 
 * @return string Normalized path
 * @see \core\helpers\FileHelper::normalizePath()
 */
function normalizePath(string $path): string
{
	return \core\helpers\FileHelper::normalizePath($path);
}

/**
 * Indicates user is authenticated.
 * 
 * @return bool
 */
function isAuth(): bool
{
	return Sys::$app->user->isAuth;
}

/**
 * Indicates user is not authenticated.
 * 
 * @return bool
 */
function isGuest(): bool
{
	return Sys::$app->user->isGuest;
}

/**
 * Indicates user is administrator.
 * 
 * @return bool
 */
function isAdmin(): bool
{
	return isAuth() && Sys::$app->user->identity->role->isAdmin();
}

/**
 * Indicates user is content manager.
 * 
 * @return bool
 */
function isContentManager(): bool
{
	return isAuth() && Sys::$app->user->identity->role->isContentManager();
}

/**
 * Whether is "production" environment.
 * 
 * @return bool
 * @throws Exception If environment constant not is defined.
 */
function isProdEnv(): bool
{
	if (!defined('SYS_ENV')) {
		throw new Exception("Constant 'SYS_ENV' is not defined!");
	}

	return SYS_ENV === 'prod';
}

/**
 * Whether is debug environment
 * 
 * @return bool
 * @throws Exception If debug constant not is defined.
 */
function isDebugEnv(): bool
{
	if (!defined('SYS_DEBUG')) {
		throw new Exception("Constant 'SYS_DEBUG' is not defined!");
	}

	return SYS_DEBUG === true;
}