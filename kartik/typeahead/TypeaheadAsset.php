<?php

declare(strict_types=1);

namespace kartik\typeahead;

use kartik\base\AssetBundle;

/**
 * Asset bundle for Typeahead advanced widget
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class TypeaheadAsset extends AssetBundle
{
    public string $basePath = '/assets/kartik/typeahead/';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setupAssets('css', [
            'css/typeahead',
            'css/typeahead-kv'
        ]);
        $this->setupAssets('js', [
            'js/typeahead.bundle',
            'js/typeahead-kv'
        ]);
        parent::init();
    }
}
