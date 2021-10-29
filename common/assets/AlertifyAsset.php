<?php

declare(strict_types=1);

namespace common\assets;

use core\web\AssetBundle;

class AlertifyAsset extends AssetBundle
{
	public array $js = [
		'/assets/alertify/alertify.min.js',
		'/assets/alertify/config_ru.js',
	];

	public array $css = [
		'/assets/alertify/css/alertify.min.css',
		'/assets/alertify/css/themes/default.min.css',
		// '/assets/alertify/css/themes/bootstrap.min.css',
		// '/assets/alertify/css/themes/semantic.min.css',

	];
}
