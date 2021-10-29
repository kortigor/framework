<?php

declare(strict_types=1);

namespace common\assets;

use core\web\AssetBundle;

class SortableListsAsset extends AssetBundle
{
	public string $basePath = '/assets/sortable-lists/';

	public array $js = [
		'jquery-sortable-lists-fixed.js', // Use fixed version
		// 'jquery-sortable-lists.min.js',
		// 'jquery-sortable-lists-old.js', // Old version
		// 'jquery-sortable-lists-mobile.min.js',
	];

	public array $css = [
		'sortable-lists.css',
	];

	public array $depends = [
		\common\assets\JqueryAsset::class,
	];
}
