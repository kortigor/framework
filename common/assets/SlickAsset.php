<?php

declare(strict_types=1);

namespace common\assets;

use core\web\AssetBundle;

class SlickAsset extends AssetBundle
{
	public string $basePath = '/assets/slick/';

	public array $js = [
		'slick.min.js',
	];

	public array $css = [
		'slick.css',
		'slick-theme.css',
	];

	public array $depends = [
		\common\assets\JqueryAsset::class,
		\core\bootstrap4\BootstrapAsset::class
	];
}
