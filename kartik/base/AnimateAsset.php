<?php

declare(strict_types=1);

namespace kartik\base;

/**
 * Asset bundle for loading animations.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 */
class AnimateAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setupAssets('css', ['/assets/kartik/base/css/animate']);
        parent::init();
    }
}