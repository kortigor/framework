<?php

declare(strict_types=1);

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014 - 2019
 * @version 1.0.4
 */

namespace kartik\typeahead;

use kartik\base\AssetBundle;

/**
 * Asset bundle for Typeahead Handle Bars plugin
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class TypeaheadHBAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->basePath = '/assets/kartik/typeahead/';

        $this->setupAssets('js', ['js/handlebars']);
        parent::init();
    }
}