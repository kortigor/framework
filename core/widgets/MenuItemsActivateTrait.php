<?php

declare(strict_types=1);

namespace core\widgets;

use Closure;
use core\http\Uri;
use core\http\UriComparator;
use core\helpers\ArrayHelper;
use core\helpers\Url;

/**
 * Menu items activation trait.
 * 
 * @author Igor Kort <kort.igor@gmail.com>
 */
trait MenuItemsActivateTrait
{
    /**
     * Url compare method to activate menu items.
     * 
     * @var string
     * @see \core\http\UriComparator for available comparison methods.
     */
    public string $uriCompareMethod = UriComparator::QUERY_ENTRY;

    /**
     * @var int Path level to compare, it does matter only if UriComparator::PATH_LEVEL compare method used
     */
    public int $pathLevel = 1;

    /**
     * @var bool whether to automatically activate items according to currently requested url.
     * @see isItemActive()
     */
    public bool $activateItems = true;

    /**
     * @var string the CSS class to be appended to the active menu item.
     */
    public string $activeCssClass = 'active';

    /**
     * Checks whether a menu item is active.
     * 
     * This is done by compare item Url and current application Url.
     * Only when item Url and current application Url is equal by specific method,
     * will a menu item be considered active.
     * 
     * @param $item the menu item to be checked
     * @return bool whether the menu item is active
     * 
     * @see \core\http\UriComparator for available comparison methods.
     */
    protected function isItemActive($item): bool
    {
        if (!$this->activateItems) {
            return false;
        }

        if (isset($item['active'])) {
            $active = ArrayHelper::getValue($item, 'active', false);

            if (is_bool($active)) {
                return $active;
            }

            if ($active instanceof Closure) {
                return $active(Url::current());
            }

            return false;
        }

        if (isset($item['url'])) {
            $method = $this->uriCompareMethod;
            return UriComparator::$method(new Uri(Url::current()), new Uri($item['url']), $this->pathLevel);
        }

        return false;
    }
}
