<?php

declare(strict_types=1);

namespace core\activeform;

use core\web\AssetBundle;

/**
 * The asset bundle for the ActiveForm widget.
 */
class ActiveFormAsset extends AssetBundle
{
    public array $js = [
        '/assets/activeform/activeForm.js',
    ];

    public array $depends = [
        \common\assets\CoreAssetJs::class,
    ];
}