<?php

declare(strict_types=1);

namespace core\bootstrap4;

use core\helpers\ArrayHelper;

/**
 * NavLinkButtonGroup renders a "button group" bootstrap component, using ancors instead buttons.
 * Also it uses Nav functionality to activate items by adding 'active' class.
 *
 * For example:
 *
 * ```php
 * echo NavButtonGroup::widget([
 *     'items' => [
 *         [
 *             'label' => 'Home',
 *             'url' => ['site/index'],
 *             'options' => [...],
 *         ],
 *         
 *         [
 *             'label' => 'Login',
 *             'url' => ['site/login'],
 *             'visible' => $app->user->isGuest
 *         ],
 *     ],
 *     'options' => ['class' =>'mb-3'], // set custom class
 * ]);
 * ```
 *
 * @see https://getbootstrap.com/docs/4.4/components/button-group/
 *
 * @author Igor Kort <kort.igor@gmail.com>
 */
class NavLinkButtonGroup extends Nav
{
    /**
     * @var string
     */
    public string $buttonsClass = 'btn btn-light';

    /**
     * Initializes the widget.
     */
    public function init()
    {
        Html::addCssClass($this->options, ['widget' => 'btn-group']);
        if (!isset($this->options['role'])) {
            $this->options['role'] = 'group';
        }

        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
    }

    /**
     * Renders widget items.
     * 
     * @throws InvalidConfigException
     */
    public function renderItems(): string
    {
        $items = [];
        foreach ($this->items as $i => $item) {
            if (isset($item['visible']) && !$item['visible']) {
                continue;
            }
            $items[] = $this->renderLinkAsButton($item);
        }

        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'div');

        return Html::tag($tag, implode("\n", $items), $options);
    }

    /**
     * Renders a widget's item.
     * @param string|array $item the item to render.
     * @return string the rendering result.
     * @throws InvalidConfigException
     * @throws \Exception
     */
    public function renderLinkAsButton($item): string
    {
        if (is_string($item)) {
            return $item;
        }
        if (!isset($item['label'])) {
            throw new \Exception("The 'label' option is required.");
        }
        $encodeLabel = $item['encode'] ?? $this->encodeLabels;
        $label = $encodeLabel ? Html::encode($item['label']) : $item['label'];
        $options = ArrayHelper::getValue($item, 'options', []);
        $url = ArrayHelper::getValue($item, 'url', '#');
        $active = $this->isItemActive($item);

        Html::addCssClass($options, $this->buttonsClass);
        $options = ArrayHelper::merge($options, $this->itemOptions ?? []);

        if ($this->activateItems && $active) {
            Html::addCssClass($options, 'active');
        }

        return Html::a($label, $url, $options);
    }
}
