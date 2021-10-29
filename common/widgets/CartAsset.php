<?php

declare(strict_types=1);

namespace common\widgets;

use core\web\AssetBundle;

class CartAsset extends AssetBundle
{
    public array $js = [
        '/assets/widgets/cart/cart.js',
        '/assets/cookie/js.cookie.min.js',
    ];

    public array $depends = [
        \common\assets\JqueryAsset::class,
    ];
}