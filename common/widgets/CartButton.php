<?php

declare(strict_types=1);

namespace common\widgets;

use core\helpers\Html;

/**
 * Add to cart button
 */
class CartButton extends \core\widgets\Widget
{
    /**
     * @var string Item id handled by this button.
     */
    public string $id;

    /**
     * @var array Array of item ids added to cart.
     */
    public array $items = [];

    /**
     * @var string Button text when item not added to cart
     */
    public string $textNotAdded;

    /**
     * @var string Button text when item was added to cart
     */
    public string $textAdded;

    /**
     * @var string Message text when item added to cart
     */
    public string $messageAdded = '';

    /**
     * @var string Message text when item removed from cart
     */
    public string $messageRemoved = '';

    /**
     * @var string Icon before button text when item not added to cart
     */
    public string $iconNotAdded = '<i class="fas fa-fw fa-lg fa-cart-plus"></i> ';

    /**
     * @var string Icon before button text when item was added to cart
     */
    public string $iconAdded = '<i class="fas fa-fw fa-lg fa-cart-arrow-down"></i> ';

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
        Html::addCssClass($options, 'add-to-cart');
        if ($this->isItemAdded()) {
            Html::addCssClass($options, 'added');
        }

        echo Html::button($btnText, $options);
    }

    /**
     * Whether item added to cart
     * 
     * @return bool
     */
    protected function isItemAdded(): bool
    {
        return in_array($this->id, array_keys($this->items));
    }

    protected function registerScript(): void
    {
        $view = $this->getView();
        CartAsset::register($view);
    }
}