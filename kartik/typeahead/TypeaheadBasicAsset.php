<?php

declare(strict_types=1);

namespace kartik\typeahead;

use kartik\base\AssetBundle;

/**
 * Asset bundle for Typeahead Widget (Basic)
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class TypeaheadBasicAsset extends AssetBundle
{
    public string $basePath = '/assets/kartik/typeahead/';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setupAssets('css',           [
            'css/typeahead',
            'css/typeahead-kv'
        ]);
        $this->setupAssets('js', [
            'js/typeahead.jquery',
            'js/typeahead-kv'
        ]);
        parent::init();
    }
}