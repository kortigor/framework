<?php

declare(strict_types=1);

namespace common\assets;

use core\web\AssetBundle;

class JsonView extends AssetBundle
{
	public array $js = [
		'/assets/json-viewer/jquery.json-viewer.js',
	];

	public array $css = [
		'/assets/json-viewer/jquery.json-viewer.css',
	];

	public array $depends = [
		\common\assets\JqueryAsset::class,
	];
}
