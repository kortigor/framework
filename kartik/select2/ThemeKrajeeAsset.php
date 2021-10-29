<?php

declare(strict_types=1);

namespace kartik\select2;

/**
 * Asset bundle for the Krajee theme for [[Select2]] widget.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class ThemeKrajeeAsset extends ThemeAsset
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->initTheme();
        $this->setupAssets('css', ['css/select2-krajee']);
        parent::init();
    }
}
