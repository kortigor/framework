<?php

declare(strict_types=1);

namespace core\activeform;

use core\web\AssetBundle;

/**
 * The asset bundle for the `MaskedInput` widget.
 *
 * Includes client assets of jQuery input mask plugin (https://github.com/RobinHerbots/Inputmask).
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 */
class MaskedInputAsset extends AssetBundle
{
    public array $js = [
        '/assets/inputmask/jquery.inputmask.bundle.js',
        // '/assets/inputmask/jquery.inputmask.min.js',
        // '/assets/inputmask/bindings/inputmask.binding.js',
    ];
    public array $depends = [
        \common\assets\CoreAssetJs::class,
    ];
}