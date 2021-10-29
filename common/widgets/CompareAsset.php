<?php

declare(strict_types=1);

namespace common\widgets;

use core\web\AssetBundle;

class CompareAsset extends AssetBundle
{
    public array $js = [
        '/assets/widgets/compare/compare.js',
        '/assets/cookie/js.cookie.min.js',
    ];

    public array $depends = [
        \common\assets\JqueryAsset::class,
    ];
}