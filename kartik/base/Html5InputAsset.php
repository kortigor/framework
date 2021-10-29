<?php

declare(strict_types=1);

namespace kartik\base;

/**
 * Asset bundle for the [[Html5Input]] widget.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 */
class Html5InputAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setupAssets('css', ['/assets/kartik/base/css/html5input']);
        parent::init();
    }
}