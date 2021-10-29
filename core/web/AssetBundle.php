<?php

declare(strict_types=1);

namespace core\web;

use core\web\View;

/**
 * Description of .js .css bundle
 */
abstract class AssetBundle
{
	/**
	 * @var string Web-accessible directory where files of this bundle is located.
	 */
	public string $basePath;

	/**
	 * @var string[] Array of url of bundle's js files
	 */
	public array $js = [];

	/**
	 * @var string[] Array of urls of bundle's css files
	 */
	public array $css = [];

	/**
	 * @var array Associative array of js files options, interprets as html attributes of <script> tag.
	 * 
	 * Exclusion: 'position' path defines place <script> tag in page:
	 * - View::POS_HEAD: 'head' page section, default.
	 * - View::POS_BODY_BEGIN: 'body' section, in begin.
	 * - View::POS_BODY_END: 'body' section, at the end.
	 * - View::POS_LOAD: inside jQuery(window).load().
	 * - View::POS_READY: inside jQuery(document).ready().
	 * 
	 * @see View
	 */
	public array $jsOptions = [];

	/**
	 * @var array Associative array of css files options, interprets as html attributes of <link> tag
	 */
	public array $cssOptions = [];

	/**
	 * @var AssetBundle[] Class names on which depends this bundle.
	 * If given, their contents are loaded BEFORE the contents of this bundle.
	 */
	public array $depends = [];

	/**
	 * Register bundle in View.
	 * 
	 * It convenient for use directly in the view.
	 * 
	 * @param View $view
	 * 
	 * @return static the registered asset bundle instance
	 */
	public static function register(View $view)
	{
		return $view->registerAssetBundle(get_called_class());
	}

	/**
	 * Register bundle files in View.
	 * 
	 * @param View $view.
	 * 
	 * @return void
	 */
	public function registerAssetFiles(View $view): void
	{
		$js = $this->resolvePath($this->js);
		$css = $this->resolvePath($this->css);

		foreach ($js as $source) {
			$view->registerJsFile($source, $this->jsOptions);
		}

		foreach ($css as $source) {
			$view->registerCssFile($source, $this->cssOptions);
		}
	}

	/**
	 * Resolve bundle paths according base path
	 * 
	 * @param array $files list of js or css files of asset
	 * 
	 * @return array
	 * @see AssetBundle::$basePath;
	 */
	protected function resolvePath(array $files): array
	{
		if (!isset($this->basePath) || !$files) {
			return $files;
		}

		foreach ($files as $source) {
			$resolved[] = $source[0] === '/' ? $source : $this->basePath . $source;
		}

		return $resolved;
	}
}