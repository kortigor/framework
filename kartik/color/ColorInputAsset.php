<?php

declare(strict_types=1);

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014 - 2020
 * @subpackage widget-colorinput
 * @version 1.0.6
 */

namespace kartik\color;

use kartik\base\AssetBundle;

/**
 * Asset bundle for ColorInput Widget
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class ColorInputAsset extends AssetBundle
{
    public string $basePath = '/assets/kartik/color/';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setupAssets('css', [
            'css/spectrum',
            'css/spectrum-kv'
        ]);
        $this->setupAssets('js', [
            'js/spectrum',
            'js/spectrum-kv'
        ]);
        parent::init();
    }
}