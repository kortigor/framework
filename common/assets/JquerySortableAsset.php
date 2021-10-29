<?php

declare(strict_types=1);

namespace common\assets;

use core\web\AssetBundle;

class JquerySortableAsset extends AssetBundle
{
	public array $js = [
		'/assets/jquery/jquery-ui-sortable.min.js',
		'/assets/widgets/sortabletable/sortableTable.js'
	];

	public array $css = [
		'/assets/widgets/sortabletable/sortable.css'
	];

	public array $depends = [
		\common\assets\JqueryAsset::class,
	];
}
