<?php

namespace backend\widgets;

use core\helpers\{
    ArrayHelper,
    Html,
    Url
};

/**
 * Class Menu
 * Side bar menu widget.
 */
class MenuAdminLTE extends \core\widgets\Menu
{
    /**
     * {@inheritdoc}
     */
    public string $linkTemplate = '<a class="nav-link {active}" href="{url}">{icon} {label}</a>';

    /**
     * {@inheritdoc}
     * Styles all labels of items on sidebar by AdminLTE
     */
    public string $labelTemplate = '<p>{label} {submenu} {badge}</p>';

    /**
     * {@inheritdoc}
     */
    public string $submenuTemplate = "\n<ul class='nav nav-treeview'>\n{items}\n</ul>\n";

    /**
     * {@inheritdoc}
     */
    public bool $activateParents = true;

    /**
     * @var string
     */
    public string $defaultIconHtml = '<i class="far fa-circle nav-icon"></i> ';

    /**
     * {@inheritdoc}
     */
    public array $options = ['class' => 'nav nav-pills nav-sidebar flex-column', 'data-widget' => 'treeview'];

    /**
     * @var string is type that will be added to $item['icon'] if it exist.
     * Font Awesome 5 added different icon types intead of everything starting with "fa fa-"
     * Possible types are fab (brand), fas (solid), far (regular), fal (light), fad (duotone). 
     * Some of them are only available for pro version of FA so check the https://fontawesome.com website
     */
    public static string $iconClassType = 'fas';

    /**
     * @var string
     */
    public static string $iconClassPrefix = 'fa-';

    public function init()
    {
        \backend\assets\AdminLTEAsset::register($this->getView());
    }

    /**
     * Renders the menu.
     */
    public function run()
    {
        $items = $this->normalizeItems($this->items, $hasActiveChild);
        if (!empty($items)) {
            $options = $this->options;
            $tag = ArrayHelper::remove($options, 'tag', 'ul');
            echo Html::tag($tag, $this->renderItems($items), $options);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function renderItem($item)
    {
        if ($item['header']) {
            return $item['label'];
        }

        $submenu = '';
        if (isset($item['items'])) {
            $active = $item['active'] ? $this->activeCssClass : '';
            $submenu = '<i class="right fas fa-angle-left"></i>';
            $labelTemplate = '<a class="nav-link ' . $active . '" href="{url}">{icon} {label}</a>';
            $linkTemplate = '<a class="nav-link ' . $active . '" href="{url}">{icon} {label}</a>';
        } else {
            $labelTemplate = $this->labelTemplate;
            $linkTemplate = $this->linkTemplate;
        }

        $replacements = [
            '{label}' => strtr(
                $this->labelTemplate,
                [
                    '{label}' => $item['label'],
                    '{badge}' => '<span class="right badge ' . ($item['badgeOptions']['class'] ?? 'badge-info') . '">' . $item['badge'] . '</span>',
                    '{submenu}' => $submenu
                ]
            ),

            '{icon}' => empty($item['icon'])
                ? $this->defaultIconHtml
                : '<i class="nav-icon ' . ($item['iconType'] ?? static::$iconClassType) . ' ' . static::$iconClassPrefix . $item['icon'] . '"></i> ',

            '{url}' => $item['url'] ?? 'javascript:void(0);',
            '{active}' => $item['active'] ? $this->activeCssClass : '',
            // If item doesn't have url, make sure these placeholders get removed from output
            '{badge}' => '',
            '{submenu}' => ''
        ];

        $template = ArrayHelper::getValue($item, 'template', isset($item['url']) ? $linkTemplate : $labelTemplate);

        return strtr($template, $replacements);
    }

    /**
     * Recursively renders the menu items (without the container tag).
     * @param array $items the menu items to be rendered recursively
     * @return string the rendering result
     */
    protected function renderItems($items)
    {
        $n = count($items);
        $lines = [];
        foreach ($items as $i => $item) {
            $options = array_merge($this->itemOptions, ArrayHelper::getValue($item, 'options', []));
            $tag = ArrayHelper::remove($options, 'tag', 'li');
            $class = $item['header'] ? ['nav-header'] : ['nav-item'];
            if ($i === 0 && isset($this->firstItemCssClass)) {
                $class[] = $this->firstItemCssClass;
            }
            if ($i === $n - 1 && isset($this->lastItemCssClass)) {
                $class[] = $this->lastItemCssClass;
            }
            if (!empty($class)) {
                if (empty($options['class'])) {
                    $options['class'] = implode(' ', $class);
                } else {
                    $options['class'] .= ' ' . implode(' ', $class);
                }
            }
            $menu = $this->renderItem($item);
            if (!empty($item['items'])) {
                $menu .= strtr($this->submenuTemplate, [
                    '{items}' => $this->renderItems($item['items']),
                ]);
                if (isset($options['class'])) {
                    $options['class'] .= ' treeview';
                } else {
                    $options['class'] = 'treeview';
                }
                if ($item['active']) {
                    $options['class'] .= ' menu-open';
                }
            }
            $lines[] = Html::tag($tag, $menu, $options);
        }
        return implode("\n", $lines);
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeItems($items, &$active)
    {
        foreach ($items as $i => $item) {
            if (isset($item['visible']) && !$item['visible']) {
                unset($items[$i]);
                continue;
            }

            if (isset($item['url']) && is_array($item['url'])) {
                $item['url'] = Url::to($item['url']);
                $items[$i]['url'] = $item['url'];
            }

            $item['label'] ??= '';
            $encodeLabel = $item['encode'] ?? $this->encodeLabels;
            $items[$i]['label'] = $encodeLabel ? Html::encode($item['label']) : $item['label'];
            $items[$i]['icon'] = $item['icon'] ?? '';
            $items[$i]['header'] = ArrayHelper::getValue($item, 'header', false);
            $items[$i]['badge'] = $item['badge'] ?? '';
            $items[$i]['badgeOptions'] = $item['badgeOptions'] ?? '';
            $hasActiveChild = false;
            if (isset($item['items'])) {
                $items[$i]['items'] = $this->normalizeItems($item['items'], $hasActiveChild);
                if (empty($items[$i]['items']) && $this->hideEmptyItems) {
                    unset($items[$i]['items']);
                    if (!isset($item['url'])) {
                        unset($items[$i]);
                        continue;
                    }
                }
            }

            if ($this->activateParents && $hasActiveChild || $this->activateItems && $this->isItemActive($item)) {
                $active = $items[$i]['active'] = true;
            } else {
                $items[$i]['active'] = false;
            }
        }
        return array_values($items);
    }
}