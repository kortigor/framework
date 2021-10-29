<?php

namespace core\bootstrap4;

use core\web\AssetBundle;

/**
 * The asset bundle for Bootstrap4 framework.
 */
class BootstrapAsset extends AssetBundle
{
	public array $js = [
		'/assets/bootstrap/js/bootstrap.bundle.min.js',
		'/assets/bootstrap/bs-custom-file-input.min.js',
	];

	public array $css = [
		'/assets/bootstrap/css/bootstrap.min.css',
	];

	public array $depends = [
		\common\assets\JqueryAsset::class,
	];
}
