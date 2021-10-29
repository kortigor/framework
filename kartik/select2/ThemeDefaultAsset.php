<?php

declare(strict_types=1);

namespace kartik\select2;

/**
 * Asset bundle for the default inbuilt theme for [[Select2]] widget.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class ThemeDefaultAsset extends ThemeAsset
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->initTheme();
        $this->setupAssets('css', ['css/select2-default']);
        parent::init();
    }
}
