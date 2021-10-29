<?php

declare(strict_types=1);

namespace kartik\select2;

/**
 * Asset bundle for the Krajee theme for [[Select2]] widget.
 *
 * @author Mohamad Faeez <mfmdevsystem@gmail.com>
 * @modified Kartik Visweswaran <kartikv2@gmail.com>
 * @since 2.2.1
 */
class ThemeMaterialAsset extends ThemeAsset
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->initTheme();
        $this->setupAssets('css', ['css/select2-material']);
        parent::init();
    }
}