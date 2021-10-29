<?php

declare(strict_types=1);

namespace common\assets;

use core\web\AssetBundle;

class CoreAsset extends AssetBundle
{
	public array $js = [
		// '/assets/holder.min.js',
	];

	public array $css = [
		'/css/common.min.css',
	];

	public array $depends = [
		\common\assets\CoreAssetJs::class,
		\core\bootstrap4\BootstrapAsset::class
	];
}
