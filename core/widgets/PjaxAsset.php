<?php

namespace core\widgets;

use core\web\AssetBundle;

/**
 * This asset bundle provides the javascript files required by Pjax widget.
 */
class PjaxAsset extends AssetBundle
{
    public array $js = [
        '/assets/jquery/jquery.pjax.js',
    ];

    public array $depends = [
        \common\assets\JqueryAsset::class,
    ];
}