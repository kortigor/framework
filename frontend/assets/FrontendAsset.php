<?php

declare(strict_types=1);

namespace frontend\assets;

use core\web\AssetBundle;

class FrontendAsset extends AssetBundle
{
	public array $js = [
		'/js/site.js',
	];

	public array $css = [
		'/css/site.min.css',
		'/css/publications.min.css',
	];

	public array $depends = [
		\common\assets\FontawesomeAsset::class,
		\common\assets\FancyboxAsset::class,
		\common\assets\AlertifyAsset::class,
		\common\assets\CoreAsset::class,
	];
}