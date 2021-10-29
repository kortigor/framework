<?php

namespace backend\assets;

use core\web\AssetBundle;

/**
 * The asset bundle for AdminLTE admin panel.
 */
class AdminLTEAsset extends AssetBundle
{
	public array $js = [
		// '/admin/assets/adminlte/js/AdminLTE.js',
		'/admin/assets/adminlte/js/adminlte.min.js',
	];

	public array $css = [
		// '/admin/assets/adminlte/css/alt/adminlte.core.css',
		'/admin/assets/adminlte/css/adminlte.min.css',
	];

	public array $depends = [
		\core\bootstrap4\BootstrapAsset::class,
	];
}