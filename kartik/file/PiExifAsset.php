<?php

declare(strict_types=1);

namespace kartik\file;

/**
 * PiExif Asset bundle for FileInput Widget
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class PiExifAsset extends BaseAsset
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setupAssets('js', ['js/plugins/piexif']);
        parent::init();
    }
}
