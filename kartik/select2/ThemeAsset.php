<?php

declare(strict_types=1);

namespace kartik\select2;

use kartik\base\AssetBundle;

/**
 * Base theme asset bundle.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class ThemeAsset extends AssetBundle
{
    public array $depends = [
        Select2KrajeeAsset::class
    ];

    /**
     * Initializes theme
     */
    protected function initTheme()
    {
        $this->basePath = '/assets/kartik/select2/';
    }
}
