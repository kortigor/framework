<?php

declare(strict_types=1);

namespace kartik\select2;

use kartik\base\AssetBundle;

/**
 * Krajee asset bundle for [[Select2]] Widget.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class Select2KrajeeAsset extends AssetBundle
{
    public string $basePath = '/assets/kartik/select2/';

    public array $depends = [
        Select2Asset::class
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setupAssets('css', ['css/select2-addl']);
        $this->setupAssets('js', ['js/select2-krajee']);
        parent::init();
    }
}
