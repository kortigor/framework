<?php

declare(strict_types=1);

namespace kartik\base;

/**
 * Common base widget asset bundle for all Krajee widgets
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 */
class WidgetAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setupAssets('css', ['/assets/kartik/base/css/kv-widgets']);
        $this->setupAssets('js', ['/assets/kartik/base/js/kv-widgets']);
        parent::init();
    }
}