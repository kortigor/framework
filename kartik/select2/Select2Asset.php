<?php

declare(strict_types=1);

namespace kartik\select2;

use kartik\base\AssetBundle;

/**
 * Asset bundle for [[Select2]] Widget. Includes assets from select2 plugin library.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class Select2Asset extends AssetBundle
{
    public string $basePath = '/assets/jquery-select2/';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setupAssets('css', ['css/select2']);
        $this->setupAssets('js', ['js/select2.full']);
        parent::init();
    }
}
