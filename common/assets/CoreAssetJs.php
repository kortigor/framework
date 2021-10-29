<?php

declare(strict_types=1);

namespace common\assets;

use core\web\AssetBundle;

class CoreAssetJs extends AssetBundle
{
	public array $js = [
		'/js/sys.js',
		'/js/common.js',
	];

	public array $depends = [
		\common\assets\JqueryAsset::class,
	];
}
