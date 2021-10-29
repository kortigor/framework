<?php

namespace backend\assets;

use core\web\AssetBundle;

class BackendAsset extends AssetBundle
{
	public array $js = [
		'/admin/js/site.js',
		'/assets/cookie/js.cookie.min.js',
	];

	public array $css = [
		'/admin/css/site.min.css',
	];

	public array $depends = [
		\backend\assets\AdminLTEAsset::class,
		\common\assets\FontawesomeAsset::class,
		\common\assets\AlertifyAsset::class,
		\common\assets\FancyboxAsset::class,
		\common\assets\CoreAsset::class,
	];
}