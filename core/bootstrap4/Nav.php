<?php

declare(strict_types=1);

namespace core\bootstrap4;

use core\helpers\ArrayHelper;
use core\helpers\Url;
use core\widgets\MenuItemsActivateTrait;

/**
 * Nav renders a nav HTML component.
 *
 * For example:
 *
 * ```php
 * echo Nav::widget([
 *     'items' => [
 *         [
 *             'label' => 'Home',
 *             'url' => ['site/index'],
 *             'linkOptions' => [...],
 *         ],
 *         [
 *             'label' => 'Dropdown',
 *             'items' => [
 *                  ['label' => 'Level 1 - Dropdown A', 'url' => '#'],
 *                  '<div class="dropdown-divider"></div>',
 *                  '<div class="dropdown-header">Dropdown Header</div>',
 *                  ['label' => 'Level 1 - Dropdown B', 'url' => '#'],
 *             ],
 *         ],
 *         [
 *             'label' => 'Login',
 *             'url' => ['site/login'],
 *             'visible' => $app->user->isGuest
 *         ],
 *     ],
 *     'options' => ['class' =>'nav-pills'], // set this to nav-tabs to get tab-styled navigation
 * ]);
 * ```
 *
 * Note: Multilevel dropdowns beyond Level 1 are not supported in Bootstrap 3.
 *
 * @see https://getbootstrap.com/components/#dropdowns
 * @see https://getbootstrap.com/components/#nav
 *
 * @author Antonio Ramirez <amigo.cobos@gmail.com> & Igor Kort <kort.igor@gmail.com>
 */
class Nav extends Widget
{
    use MenuItemsActivateTrait;

    /**
     * @var array list of items in the nav widget. Each array element represents a single
     * menu item which can be either a string or an array with the following structure:
     *
     * - label: string, required, the nav item label.
     * - url: optional, the item's URL. Defaults to "#".
     * - visible: bool, optional, whether this menu item is visible. Defaults to true.
     * - disabled: bool, optional, whether this menu item is disabled. Defaults to false.
     * - linkOptions: array, optional, the HTML attributes of the item's link.
     * - options: array, optional, the HTML attributes of the item container (LI).
     * - active: bool|Closure, optional, whether the item should be on active state or not.
     * - dropdownOptions: array, optional, the HTML options that will passed to the [[Dropdown]] widget.
     * - items: array|string, optional, the configuration array for creating a [[Dropdown]] widget,
     *   or a string representing the dropdown menu. Note that Bootstrap does not support sub-dropdown menus.
     * - encode: bool, optional, whether the label will be HTML-encoded. If set, supersedes the $encodeLabels option for only this item.
     *
     * If a menu item is a string, it will be rendered directly without HTML encoding.
     */
    public array $items = [];

    /**
     * @var bool whether the nav items labels should be HTML-encoded.
     */
    public bool $encodeLabels = true;

    /**
     * @var bool whether to activate parent menu items when one of the corresponding child menu items is active.
     */
    public bool $activateParents = false;

    /**
     * @var string name of a class to use for rendering dropdowns within this widget. Defaults to [[Dropdown]].
     */
    public $dropdownClass = Dropdown::class;

    /**
     * Initializes the widget.
     */
    public function init()
    {
        parent::init();
        Html::addCssClass($this->options, ['widget' => 'nav']);
    }

    /**
     * Renders the widget.
     * @throws InvalidConfigException
     */
    public function run()
    {
        $this->init();
        return $this->renderItems();
    }

    /**
     * Renders widget items.
     * 
     * @throws InvalidConfigException
     */
    public function renderItems()
    {
        $items = [];
        foreach ($this->items as $i => $item) {
            if (isset($item['visible']) && !$item['visible']) {
                continue;
            }
            $items[] = $this->renderItem($item);
        }

        return Html::tag('ul', implode("\n", $items), $this->options);
    }

    /**
     * Renders a widget's item.
     * @param string|array $item the item to render.
     * @return string the rendering result.
     * @throws InvalidConfigException
     * @throws \Exception
     */
    public function renderItem($item)
    {
        if (is_string($item)) {
            return $item;
        }
        if (!isset($item['label'])) {
            throw new \Exception("The 'label' option is required.");
        }
        if (isset($item['url']) && is_array($item['url'])) {
            $item['url'] = Url::to($item['url']);
        }

        $encodeLabel = $item['encode'] ?? $this->encodeLabels;
        $label = $encodeLabel ? Html::encode($item['label']) : $item['label'];
        $options = ArrayHelper::getValue($item, 'options', []);
        $items = ArrayHelper::getValue($item, 'items');
        $url = ArrayHelper::getValue($item, 'url', '#');
        $linkOptions = ArrayHelper::getValue($item, 'linkOptions', []);
        $disabled = ArrayHelper::getValue($item, 'disabled', false);
        $active = $this->isItemActive($item);

        if (empty($items)) {
            $items = '';
        } else {
            $linkOptions['data-toggle'] = 'dropdown';
            Html::addCssClass($options, ['widget' => 'dropdown']);
            Html::addCssClass($linkOptions, ['widget' => 'dropdown-toggle']);
            if (is_array($items)) {
                $items = $this->isChildActive($items, $active);
                $items = $this->renderDropdown($items, $item);
            }
        }

        Html::addCssClass($options, 'nav-item');
        Html::addCssClass($linkOptions, 'nav-link');

        if ($disabled) {
            ArrayHelper::setValue($linkOptions, 'tabindex', '-1');
            ArrayHelper::setValue($linkOptions, 'aria-disabled', 'true');
            Html::addCssClass($linkOptions, 'disabled');
        } elseif ($this->activateItems && $active) {
            Html::addCssClass($linkOptions, $this->activeCssClass);
        }

        return Html::tag('li', Html::a($label, $url, $linkOptions) . $items, $options);
    }

    /**
     * Renders the given items as a dropdown.
     * This method is called to create sub-menus.
     * @param array $items the given items. Please refer to [[Dropdown::items]] for the array structure.
     * @param array $parentItem the parent item information. Please refer to [[items]] for the structure of this array.
     * @return string the rendering result.
     * @throws \Exception
     */
    protected function renderDropdown($items, $parentItem)
    {
        /** @var Widget $dropdownClass */
        $dropdownClass = $this->dropdownClass;
        return $dropdownClass::widget([
            'options' => ArrayHelper::getValue($parentItem, 'dropdownOptions', []),
            'items' => $items,
            'encodeLabels' => $this->encodeLabels,
            'clientOptions' => false,
            'view' => $this->getView(),
        ]);
    }

    /**
     * Check to see if a child item is active optionally activating the parent.
     * @param array $items @see items
     * @param bool $active should the parent be active too
     * @return array @see items
     */
    protected function isChildActive($items, &$active)
    {
        foreach ($items as $i => $child) {
            if (is_array($child) && !ArrayHelper::getValue($child, 'visible', true)) {
                continue;
            }
            if ($this->isItemActive($child)) {
                ArrayHelper::setValue($items[$i], 'active', true);
                if ($this->activateParents) {
                    $active = true;
                }
            }
            $childItems = ArrayHelper::getValue($child, 'items');
            if (is_array($childItems)) {
                $activeParent = false;
                $items[$i]['items'] = $this->isChildActive($childItems, $activeParent);
                if ($activeParent) {
                    Html::addCssClass($items[$i]['options'], $this->activeCssClass);
                    $active = true;
                }
            }
        }
        return $items;
    }
}
