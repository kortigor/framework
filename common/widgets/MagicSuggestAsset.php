<?php

declare(strict_types=1);

namespace common\widgets;

use core\web\AssetBundle;

class MagicSuggestAsset extends AssetBundle
{
    public string $basePath = '/assets/widgets/magicsuggest/';

    public array $js = [
        'magicsuggest-min.js',
    ];

    public array $css = [
        'magicsuggest-min.css',
    ];

    public array $depends = [
        \common\assets\JqueryAsset::class,
        \core\bootstrap4\BootstrapAsset::class
    ];
}