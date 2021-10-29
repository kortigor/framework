<?php

declare(strict_types=1);

namespace core\bootstrap4;

/**
 * \core\bootstrap4\Widget is the base class for all bootstrap widgets.
 *
 * @author Antonio Ramirez <amigo.cobos@gmail.com>
 */
class Widget extends \core\widgets\Widget
{
    use BootstrapWidgetTrait;

    /**
     * @var array the HTML attributes for the widget container tag.
     * @see core\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $options = [];
}
