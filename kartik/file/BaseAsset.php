<?php

declare(strict_types=1);

namespace kartik\file;

use kartik\base\AssetBundle;

/**
 * BaseAsset is the base asset bundle class used by all FileInput widget asset bundles.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class BaseAsset extends AssetBundle
{
    public string $basePath = '/assets/kartik/bootstrap-fileinput/';
}
