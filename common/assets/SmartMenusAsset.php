<?php

declare(strict_types=1);

namespace common\assets;

use core\web\AssetBundle;

class SmartMenusAsset extends AssetBundle
{
	public string $basePath = '/assets/smartmenus/';

	public array $js = [
		'jquery.smartmenus.min.js',
		// 'addons/bootstrap-4/jquery.smartmenus.bootstrap-4.min.js',
	];

	public array $css = [
		'css/sm-core-css.css',
		'css/sm-custom/sm-custom.css',
		// 'css/sm-blue/sm-blue.css',
		// 'addons/bootstrap-4/jquery.smartmenus.bootstrap-4.css',
	];

	public array $depends = [
		\common\assets\JqueryAsset::class,
		\core\bootstrap4\BootstrapAsset::class
	];
}
