<?php

declare(strict_types=1);

namespace kartik\base;

use core\exception\InvalidConfigException;
use core\widgets\Widget as BaseWidget;

/**
 * Base class for widgets extending [[BaseWidget]] used in Krajee extensions.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 */
class Widget extends BaseWidget implements BootstrapInterface
{
    use WidgetTrait;

    /**
     * @var array HTML attributes or other settings for widgets.
     */
    public array $options = [];

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        $this->initBsVersion();
        parent::init();
        $this->mergeDefaultOptions();
        if (empty($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
        $this->initDestroyJs();
    }
}