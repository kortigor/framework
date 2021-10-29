<?php

declare(strict_types=1);

namespace kartik\file;

/**
 * Sortable asset bundle for FileInput Widget
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class SortableAsset extends BaseAsset
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setupAssets('js', ['js/plugins/sortable']);
        parent::init();
    }
}