<?php

declare(strict_types=1);

namespace kartik\base;

/**
 * Base asset bundle for Krajee extensions (including bootstrap plugins)
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 */
class PluginAssetBundle extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public bool $bsPluginEnabled = true;
}