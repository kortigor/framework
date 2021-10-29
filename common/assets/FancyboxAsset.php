<?php

declare(strict_types=1);

namespace common\assets;

use core\web\AssetBundle;

class FancyboxAsset extends AssetBundle
{
	public array $js = [
		'/assets/fancybox/jquery.fancybox.min.js',
		'/assets/fancybox/fancybox.config.js',
	];

	public array $css = [
		'/assets/fancybox/jquery.fancybox.min.css',
	];

	public array $depends = [
		\common\assets\JqueryAsset::class,
	];
}
