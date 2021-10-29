<?php

declare(strict_types=1);

namespace common\widgets;

use core\helpers\Html;

/**
 * Add to compare button
 */
class CompareButton extends \core\widgets\Widget
{
    /**
     * @var string Item id handled by this button.
     */
    public string $id;

    /**
     * @var int Maximum allowed items to add to compare
     */
    public int $limit = 2;

    /**
     * @var array Array of item ids added to compare.
     */
    public array $items = [];

    /**
     * @var string Button text when item not added to compare
     */
    public string $textNotAdded;

    /**
     * @var string Button text when item was added to compare
     */
    public string $textAdded;

    /**
     * @var string Message text when item added to compare
     */
    public string $messageAdded = '';

    /**
     * @var string Message text when item removed from compare
     */
    public string $messageRemoved = '';

    /**
     * @var string Icon before button text when item not added to compare
     */
    public string $iconNotAdded = '<i class="fas fa-fw fa-balance-scale-right"></i> ';

    /**
     * @var string Icon before button text when item was added to compare
     */
    public string $iconAdded = '<i class="fas fa-fw fa-balance-scale-right"></i> ';

    /**
     * @var array Button tag options.
     */
    public array $options = ['class' => 'btn'];

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->registerScript();
        $btnText = $this->isItemAdded() ? $this->iconAdded . $this->textAdded : $this->iconNotAdded . $this->textNotAdded;
        $options = $this->options;
        $options['data-text-added'] = $this->iconAdded . $this->textAdded;
        $options['data-text-notadded'] = $this->iconNotAdded . $this->textNotAdded;
        $options['data-message-added'] = $this->messageAdded;
        $options['data-message-removed'] = $this->messageRemoved;
        $options['data-id'] = $this->id;
        Html::addCssClass($options, 'add-to-compare');
        if ($this->isItemAdded()) {
            Html::addCssClass($options, 'added');
        }

        echo Html::button($btnText, $options);
    }

    /**
     * Whether item added to compare
     * 
     * @return bool
     */
    protected function isItemAdded(): bool
    {
        return in_array($this->id, $this->items);
    }

    protected function registerScript(): void
    {
        $view = $this->getView();
        CompareAsset::register($view);
        $view->registerJsVar('limitCompare', (int) $this->limit);
    }
}