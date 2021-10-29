<?php

declare(strict_types=1);

namespace common\assets;

use core\web\AssetBundle;

class JqueryValidationAsset extends AssetBundle
{
	public array $js = [
		// '/assets/jqueryvalidation/jquery.validate-fixed.js',
		'/assets/jqueryvalidation/jquery.validate.min.js',
		'/assets/jqueryvalidation/bootstrap4.theme.js',
		'/assets/jqueryvalidation/additional-methods.min.js',
		'/assets/jqueryvalidation/custom-methods.js',
		'/assets/jqueryvalidation/jquery.validate.hooks.js',
		'/assets/jqueryvalidation/lang/messages_ru.js',
	];

	// public array $css = [];

	public array $depends = [
		\common\assets\JqueryAsset::class,
	];
}
