<?php

declare(strict_types=1);

namespace core\web;

use InvalidArgumentException;
use core\exception\InvalidConfigException;
use core\helpers\ArrayHelper;
use core\helpers\Html;

class AssetReg
{
	const JS = 'js';

	const CSS = 'css';

	/**
	 * @var string Site root PHP path. Used to access to asset files, to get file modification date
	 * @see getFileTimestamp()
	 */
	public static string $rootPath;

	/**
	 * @var string Applications root php path and home url. Used to access to files via filesystem.
	 * 
	 * Application root web path must have only one level:
	 *  - '/admin/' - valid;
	 *  - '/admin/firstapp/' - invalid.
	 * 
	 * @see resolveRealPath()
	 * @see appByUrlPath()
	 */
	public static array $appRootPath = [
		'frontend' => [
			'web' => '/',
			'php' => '/frontend/web/',
		],
		'backend' => [
			'web' => '/admin/',
			'php' => '/backend/web/',
		],
	];

	/**
	 * @var array Internal data cached storage.
	 */
	private static array $_cache;

	/**
	 * @param string $source Asset source path. Local or external (starting from '//' or 'http(s)')
	 * @param string $typeAsset Look to class constants
	 * @param string|array|null $options Tag options
	 * 
	 * @return string tag to be added in html code
	 * @throws InvalidArgumentException if invalid asset type given
	 */
	public static function getTag(string $source, string $typeAsset, string|array $options = null): string
	{
		$options = Html::normalizeOptions($options);
		ArrayHelper::remove($options, 'position');
		$appendTimestamp = ArrayHelper::remove($options, 'appendTimestamp', true);
		if ($appendTimestamp) {
			$source = static::appendSrcVersionParam($source);
		}

		return match ($typeAsset) {
			static::CSS => Html::cssFile($source, $options),
			static::JS => Html::jsFile($source, $options),
			default => throw new InvalidArgumentException('Invalid asset type')
		};
	}

	/**
	 * Get asset path with appended "v" (version) parameter equal file timestamp.
	 * Parameter will add only for local assets.
	 * 
	 * @param string $source Asset source path.
	 * 
	 * @return string Asset source path with added version parameter like 'v=123456789'
	 */
	public static function appendSrcVersionParam(string $source): string
	{
		$source = trim($source);
		if (static::isExternal($source)) {
			return $source;
		}

		$sourceParsed = parse_url($source);
		$filePath = $sourceParsed['path'] ?? '';
		$fileTime = static::getFileTimestamp($filePath);
		if ($fileTime === null) {
			trigger_error(sprintf('Trying to register asset with invalid path: "%s"', $source));
		}

		$query = ['v' => md5(strval($fileTime))];
		if (isset($sourceParsed['query'])) {
			parse_str($sourceParsed['query'], $query);
		}

		$queryString = http_build_query($query);

		return $filePath . ($queryString ? '?' . $queryString : '');
	}

	/**
	 * Whether path is external, (starting from '//' or 'http(s)').
	 * 
	 * @param string $source
	 * 
	 * @return bool
	 */
	private static function isExternal(string $source): bool
	{
		return parse_url($source, PHP_URL_HOST) ? true : false;
	}

	/**
	 * @param string $filePath real or web path to file from site root
	 * 
	 * @return int|null if file exists - unix timestamp, else - null
	 */
	private static function getFileTimestamp(string $filePath): ?int
	{
		$resolvedPath = static::resolveRealPath($filePath);
		$resolvedPath = realpath(static::$rootPath . DS . $resolvedPath);
		return $resolvedPath ? filemtime($resolvedPath) : null;
	}

	/**
	 * @param string $path
	 * 
	 * @return string
	 */
	private static function resolveRealPath(string $path): string
	{
		// Check if file path starts from real php path, like 'frontend/web' etc
		$pattern = self::getCached('isPhpPathPattern');
		if (preg_match($pattern, $path)) {
			return $path;
		}

		$file = pathinfo($path, PATHINFO_BASENAME);
		$dir = pathinfo($path, PATHINFO_DIRNAME);
		$splitDir = static::getPathAsArray($dir);

		$app = static::appByUrlPath($path);
		$rootWeb = self::getCached('web')[$app];
		$rootPhp = self::getCached('php')[$app];

		// Remove application home url (just first path level) if present
		if ($rootWeb) {
			array_shift($splitDir);
		}

		// Prepend real file path according application
		$splitDir = array_merge($rootPhp, $splitDir);
		$splitDir[] = $file;
		$resolvedPath = implode(DS, $splitDir);

		return $resolvedPath;
	}

	/**
	 * Resolve application name by file web path
	 * 
	 * @param string $path File web path
	 * 
	 * @return string Application name
	 * @throws InvalidConfigException if application not determined.
	 */
	private static function appByUrlPath(string $path): string
	{
		$dir = pathinfo($path, PATHINFO_DIRNAME);
		$splitDir = static::getPathAsArray($dir);

		// Probe is just first level of the path
		$probe = array_shift($splitDir) ?? '';

		// Search app as compare probe with app web root path
		foreach (static::getCached('web') as $app => $value) {
			if ($probe === $value) {
				return $app;
			}
		}

		// No app was found.
		// So believe that correct app is app with no home url (frontend in most cases)
		foreach (static::getCached('web') as $app => $value) {
			if (!$value) {
				return $app;
			}
		}

		// Still not found
		throw new InvalidConfigException('Invalid application paths configuration for path: ' . $path);
	}

	/**
	 * Convert string path into array by path separator split
	 * 
	 * @param string $path
	 * 
	 * @return array
	 */
	private static function getPathAsArray(string $path): array
	{
		return array_filter(explode('/', $path));
	}

	/**
	 * Get cached data
	 * 
	 * @param string $item Cache item
	 * 
	 * @return mixed
	 * @throws InvalidConfigException if config's web path invalid
	 */
	private static function getCached(string $item): mixed
	{
		if (!isset(self::$_cache['web'], self::$_cache['php'])) {
			foreach (static::$appRootPath as $app => $data) {
				self::assertRootWebPath($data['web']);
				self::$_cache['web'][$app] = str_replace('/', '', $data['web']);
				self::$_cache['php'][$app] = static::getPathAsArray($data['php']);
				$pattern[] = preg_quote($data['php'], '#');
			}

			self::$_cache['isPhpPathPattern'] = "#^(" . implode('|', $pattern) . ")#";
		}

		return self::$_cache[$item];
	}

	/**
	 * Assert root web path is correct
	 * 
	 * @param string $path
	 * 
	 * @return void
	 * @throws InvalidConfigException if path invalid
	 */
	private static function assertRootWebPath(string $path): void
	{
		if (count(static::getPathAsArray($path)) > 1) {
			throw new InvalidConfigException('Application web root path must have only one level, path given: ' . $path);
		}
	}

	private function __construct()
	{
		// Can not instantiate
	}
}