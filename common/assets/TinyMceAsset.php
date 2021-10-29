<?php

declare(strict_types=1);

namespace common\assets;

use core\web\AssetBundle;

class TinyMceAsset extends AssetBundle
{
	public array $js = [
		'/assets/tiny_mce/tinymce.min.js',
	];

	public array $css = [];
}