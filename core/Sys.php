<?php

declare(strict_types=1);

use core\base\Application;

class Sys
{
	/**
	 * @var Application Application object.
	 */
	public static Application $app;

	/**
	 * Register PSR4 autoloader.
	 * 
	 * @param string $path Folder contains loadable classes files. Absolute path from site root.
	 * @param string $rootNS Classes root namespace.
	 * 
	 * Examples:
	 * ```
	 * namespace core\base\;
	 * namespace core\db\;
	 * ```
	 * Corresponding directories:
	 * 
	 * '/classes/base/'
	 * 
	 * '/classes/db/'
	 * 
	 * Use code:
	 * ```
	 * Sys::autoload('/classes/', 'core');
	 * ```
	 * ```
	 * namespace frontend\controllers;
	 * ```
	 * 
	 * Corresponding directory:
	 * 
	 * '/frontend/controllers/'
	 * 
	 * Use code:
	 * 
	 * ```
	 * Sys::autoload($_SERVER['DOCUMENT_ROOT']);
	 * ```
	 * @return void
	 * @throws LogicException If specified path does not exists.
	 */
	public static function autoload(string $path, string $rootNS = ''): void
	{
		$dir = realpath($path);
		if ($dir === false) {
			throw new LogicException("Folder [{$path}] does not exists, and can't be added to autoloader.");
		}

		spl_autoload_register(function (string $className) use ($dir, $rootNS) {
			$className = ltrim($className, '\\');
			if (!$lastNsPos = strrpos($className, '\\')) {
				return;
			}

			$namespace = substr($className, 0, $lastNsPos);
			if ($rootNS) {
				$namespace = str_replace($rootNS, '', $namespace); // strip namespace root
			}
			$className = substr($className, $lastNsPos + 1);
			$ns2path = str_replace('\\', DS, $namespace);
			$dir .= DS . $ns2path . DS;
			$classFile = realpathi($dir . $className . '.php');
			if ($classFile) {
				require $classFile;
			}
		});
	}

	private function __construct()
	{
		// Can not instantiate
	}
}