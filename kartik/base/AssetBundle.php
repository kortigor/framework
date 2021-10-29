<?php

declare(strict_types=1);

namespace kartik\base;

use core\web\View;

/**
 * Asset bundle used for all Krajee extensions with bootstrap and jquery dependency.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 */
class AssetBundle extends BaseAssetBundle implements BootstrapInterface
{
    use BootstrapTrait;

    /**
     * @var bool whether to enable the dependency with bootstrap asset bundle
     */
    public bool $bsDependencyEnabled = true;

    /**
     * @inheritdoc
     */
    public array $depends = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->bsDependencyEnabled) {
            $this->initBsAssets();
        }
        parent::init();
    }

    /**
     * Initialize bootstrap assets dependencies
     */
    protected function initBsAssets()
    {
        $this->depends[] = \core\bootstrap4\BootstrapAsset::class;
    }

    /**
     * Registers this asset bundle with a view after validating the bootstrap version
     * @param View $view the view to be registered with
     */
    public static function registerBundle(View $view)
    {
        static::register($view);
    }
}