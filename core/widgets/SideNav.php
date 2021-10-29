<?php

declare(strict_types=1);

namespace core\widgets;

use core\exception\InvalidConfigException;
use core\helpers\{
    Html,
    ArrayHelper
};

/**
 * A custom extended side navigation menu.
 *
 * For example:
 *
 * ```php
 * echo SideNav::widget([
 *     'items' => [
 *         [
 *             'url' => ['site/index'],
 *             'label' => 'Home',
 *             'icon' => 'home'
 *         ],
 *         [
 *             'url' => ['/site/about'],
 *             'label' => 'About',
 *             'icon' => 'info-sign',
 *             'items' => [
 *                  ['url' => '#', 'label' => 'Item 1'],
 *                  ['url' => '#', 'label' => 'Item 2'],
 *             ],
 *         ],
 *     ],
 * ]);
 * ```
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @author Kort Igor <kort.igor@gmail.com> Adaptation for Bootstrap 4.x and Fontawesome icons, added more flexibility.
 * @requires Fontawesome 5.x
 */
class SideNav extends Menu
{
    /**
     * @var string prefix for the icon in [[items]]. This string will be prepended
     * before the icon name to get the icon CSS class. This defaults to `fas fa-`
     * for usage with glyphicons available with Bootstrap.
     */
    public string $iconPrefix = 'fas fa-fw fa-';

    /**
     * @var string The sidenav heading. This is not HTML encoded
     * If not set, no heading container will be displayed.
     */
    public string $heading;

    /**
     * @var array options for the sidenav heading
     */
    public array $headingOptions = [];

    /**
     * @var array options for the sidenav container
     */
    public array $containerOptions = [];

    /**
     * @var array options for the sidenav menu items container
     */
    public array $bodyOptions = [];

    /**
     * @var string icon for a menu sub-item
     */
    public string $submenuItemIcon = '<i class="fas fa-fw fa-circle"></i> ';

    /**
     * @var string indicator for a closed sub-menu
     */
    public string $submenuIndicator = '<i class="fas fa-angle-left"></i>';

    /**
     * @var string Item link template.
     */
    public string $linkTemplate = '<a class="nav-link" href="{url}">{icon}{label}</a>';

    /**
     * @var string Submenu template.
     */
    public string $submenuTemplate = "\n<ul class='nav nav-pills flex-column'>\n{items}\n</ul>\n";

    /**
     * @var array list of sidenav menu items. Each menu item should be an array of the following structure:
     *
     * - label: string, optional, specifies the menu item label. When [[encodeLabels]] is true, the label
     *   will be HTML-encoded. If the label is not specified, an empty string will be used.
     * - icon: string, optional, specifies the glyphicon name to be placed before label.
     * - url: string or array, optional, specifies the URL of the menu item. It will be processed by [[Url::to]].
     *   When this is set, the actual menu item content will be generated using [[linkTemplate]];
     * - visible: boolean, optional, whether this menu item is visible. Defaults to true.
     * - items: array, optional, specifies the sub-menu items. Its format is the same as the parent items.
     * - active: boolean, optional, whether this menu item is in active state (currently selected).
     *   If a menu item is active, its CSS class will be appended with [[activeCssClass]].
     *   If this option is not set, the menu item will be set active automatically when the current request
     *   is triggered by [[url]]. For more details, please refer to [[isItemActive()]].
     * - template: string, optional, the template used to render the content of this menu item.
     *   The token `{url}` will be replaced by the URL associated with this menu item,
     *   and the token `{label}` will be replaced by the label of the menu item.
     *   If this option is not set, [[linkTemplate]] will be used instead.
     * - options: array, optional, the HTML attributes for the menu item tag.
     *
     */
    public array $items;

    public function init()
    {
        parent::init();
        SideNavAsset::register($this->getView());
        $this->activateParents = true;
        $this->labelTemplate = '{icon}{label}';
        $this->markTopItems();
        Html::addCssClass($this->options, 'nav nav-pills flex-column kv-sidenav');
    }

    /**
     * Renders the side navigation menu.
     * With the heading and panel containers.
     */
    public function run()
    {
        $heading = '';
        if (isset($this->heading) && $this->heading !== '') {
            $heading = Html::tag('div', $this->heading, $this->headingOptions);
        }
        $body = Html::tag('div', $this->renderMenu(), $this->bodyOptions);
        echo Html::tag('div', $heading . $body, $this->containerOptions);
        $this->registerClientScript();
    }

    /**
     * Register necessary JavaScript
     * 
     * @return mixed
     */
    protected function registerClientScript()
    {
        $js = "$('.kv-toggle').on('click', function(e) {
            e.preventDefault();
            $(this).parent().children('ul').slideToggle();
            $(this).parent().toggleClass('{$this->activeCssClass}');
            return false;
        });";
        $this->getView()->registerJs($js);
    }

    /**
     * Renders the main menu
     */
    protected function renderMenu()
    {
        $items = $this->normalizeItems($this->items, $hasActiveChild);
        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'ul');

        return Html::tag($tag, $this->renderItems($items), $options);
    }

    /**
     * Marks each topmost level item which is not a submenu
     */
    protected function markTopItems()
    {
        $items = [];
        foreach ($this->items as $item) {
            if (empty($item['items'])) {
                $item['top'] = true;
            }
            $items[] = $item;
        }
        $this->items = $items;
    }

    /**
     * Renders the content of a side navigation menu item.
     *
     * @param array $item Menu item to be rendered. Please refer to [[items]] to see what data might be in the item.
     * @return string Rendering result
     * @throws InvalidConfigException
     */
    protected function renderItem($item)
    {
        $this->validateItems($item);
        $template = ArrayHelper::getValue($item, 'template', $this->linkTemplate);
        $url = ArrayHelper::getValue($item, 'url', '#');
        if (empty($item['top'])) {
            if (empty($item['items'])) {
                if (empty($item['icon'])) {
                    $template = str_replace('{icon}', $this->submenuItemIcon . '{icon}', $template);
                }
            } else {
                $template = $item['template'] ?? '<a class="nav-link kv-toggle" href="{url}">{icon}{label}</a>';
                $indicator = Html::tag('span', $this->submenuIndicator, ['class' => 'indicator']);
                $template = str_replace('{icon}', $indicator . '{icon}', $template);
            }
        }
        $icon = empty($item['icon']) ? '' : '<span class="' . $this->iconPrefix . $item['icon'] . '"></span>&nbsp;';
        unset($item['icon'], $item['top']);
        return strtr($template, [
            '{url}' => $url,
            '{label}' => $item['label'],
            '{icon}' => $icon
        ]);
    }

    /**
     * Validates item for a valid label.
     *
     * @throws InvalidConfigException
     */
    protected function validateItems($item)
    {
        if (!isset($item['label'])) {
            throw new InvalidConfigException("The 'label' option is required.");
        }
    }
}
