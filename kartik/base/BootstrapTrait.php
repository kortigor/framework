<?php

declare(strict_types=1);

namespace kartik\base;

use core\helpers\Html;
use core\exception\InvalidConfigException;

/**
 * BootstrapTrait includes bootstrap library init and parsing methods.
 * No by Kort: The class which uses this trait, must also necessarily implement the [[BootstrapInterface]].
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @mixin BootstrapInterface
 */
trait BootstrapTrait
{
    /**
     * @var array CSS conversion mappings for BS4.x. This is set as `$key => $value` pairs where:
     * - `$key`: _string_, is the style type to be configured (one of the constants starting with `BS_`)
     * - `$value`: _string_, represents the CSS class(es) for Bootstrap 4.x
     *   If more than one CSS class is to be applied - it is represented as array of the relevant CSS classes.
     */
    public static array $bsCssMap = [
        self::BS_PANEL => 'card',
        self::BS_PANEL_HEADING => 'card-header',
        self::BS_PANEL_TITLE => 'card-title',
        self::BS_PANEL_BODY => 'card-body',
        self::BS_PANEL_FOOTER => 'card-footer',
        self::BS_PANEL_DEFAULT => '',
        self::BS_PANEL_PRIMARY => ['bg-primary', 'text-white'],
        self::BS_PANEL_SUCCESS => ['bg-success', 'text-white'],
        self::BS_PANEL_INFO => ['bg-info', 'text-white'],
        self::BS_PANEL_WARNING => ['bg-warning', 'text-white'],
        self::BS_PANEL_DANGER => ['bg-danger', 'text-white'],
        self::BS_LABEL => 'badge',
        self::BS_BADGE => ['badge', 'badge-pill'],
        self::BS_LABEL_DEFAULT => 'badge-secondary',
        self::BS_LABEL_PRIMARY => 'badge-primary',
        self::BS_LABEL_SUCCESS => 'badge-success',
        self::BS_LABEL_INFO => 'badge-info',
        self::BS_LABEL_WARNING => 'badge-warning',
        self::BS_LABEL_DANGER => 'badge-danger',
        self::BS_TABLE_ACTIVE => 'table-active',
        self::BS_TABLE_PRIMARY => 'table-primary',
        self::BS_TABLE_SUCCESS => 'table-success',
        self::BS_TABLE_INFO => 'table-info',
        self::BS_TABLE_WARNING => 'table-warning',
        self::BS_TABLE_DANGER => 'table-danger',
        self::BS_PROGRESS_BAR_ACTIVE => 'progress-bar-animated',
        self::BS_PROGRESS_BAR_PRIMARY => 'bg-primary',
        self::BS_PROGRESS_BAR_SUCCESS => 'bg-success',
        self::BS_PROGRESS_BAR_INFO => 'bg-info',
        self::BS_PROGRESS_BAR_WARNING => 'bg-warning',
        self::BS_PROGRESS_BAR_DANGER => 'bg-danger',
        self::BS_WELL => ['card', 'card-body'],
        self::BS_WELL_SM => ['card', 'card-body', 'p-2'],
        self::BS_WELL_LG => ['card', 'card-body', 'p-4'],
        self::BS_THUMBNAIL => ['card', 'card-body'],
        self::BS_NAVBAR_DEFAULT => 'navbar-light',
        self::BS_NAVBAR_TOGGLE => 'navbar-toggler',
        self::BS_NAVBAR_RIGHT => 'ml-auto',
        self::BS_NAVBAR_BTN => 'nav-item',
        self::BS_NAVBAR_FIXTOP => 'fixed-top',
        self::BS_NAV_STACKED => 'flex-column',
        self::BS_NAV_ITEM => 'nav-item',
        self::BS_NAV_LINK => 'nav-link',
        self::BS_PAGE_ITEM => 'page-item',
        self::BS_PAGE_LINK => 'page-link',
        self::BS_LIST_INLINE_ITEM => 'list-inline-item',
        self::BS_BTN_DEFAULT => 'btn-secondary',
        self::BS_IMG_RESPONSIVE => 'img-fluid',
        self::BS_IMG_CIRCLE => 'rounded-circle',
        self::BS_IMG_ROUNDED => 'rounded',
        self::BS_RADIO => 'form-check',
        self::BS_CHECKBOX => 'form-check',
        self::BS_INPUT_LG => 'form-control-lg',
        self::BS_INPUT_SM => 'form-control-sm',
        self::BS_CONTROL_LABEL => 'col-form-label',
        self::BS_TABLE_CONDENSED => 'table-sm',
        self::BS_CAROUSEL_ITEM => 'carousel-item',
        self::BS_CAROUSEL_ITEM_NEXT => 'carousel-item-next',
        self::BS_CAROUSEL_ITEM_PREV => 'carousel-item-prev',
        self::BS_CAROUSEL_ITEM_LEFT => 'carousel-item-left',
        self::BS_CAROUSEL_ITEM_RIGHT => 'carousel-item-right',
        self::BS_CAROUSEL_CONTROL_LEFT => 'carousel-control-left',
        self::BS_CAROUSEL_CONTROL_RIGHT => 'carousel-control-right',
        self::BS_HELP_BLOCK => 'form-text',
        self::BS_PULL_RIGHT => 'float-right',
        self::BS_PULL_LEFT => 'float-left',
        self::BS_CENTER_BLOCK => ['mx-auto', 'd-block'],
        self::BS_HIDE => 'd-none',
        self::BS_HIDDEN_PRINT => 'd-print-none',
        self::BS_HIDDEN_XS => ['d-none', 'd-sm-block'],
        self::BS_HIDDEN_SM => ['d-sm-none', 'd-md-block'],
        self::BS_HIDDEN_MD => ['d-md-none', 'd-lg-block'],
        self::BS_HIDDEN_LG => ['d-lg-none', 'd-xl-block'],
        self::BS_VISIBLE_PRINT => ['d-print-block', 'd-none'],
        self::BS_VISIBLE_XS => ['d-block', 'd-sm-none'],
        self::BS_VISIBLE_SM => ['d-none', 'd-sm-block', 'd-md-none'],
        self::BS_VISIBLE_MD => ['d-none', 'd-md-block', 'd-lg-none'],
        self::BS_VISIBLE_LG => ['d-none', 'd-lg-block', 'd-xl-none'],
        self::BS_FORM_CONTROL_STATIC => 'form-control-plaintext',
        self::BS_DROPDOWN_DIVIDER => 'dropdown-divider',
        self::BS_SHOW => 'show',
    ];

    /**
     * @var int|string the bootstrap library version.
     *
     * To use with bootstrap 4 - you can set this to any string starting with 4 (e.g. `4` or `4.1.1` or `4.x`)
     */
    public $bsVersion = 4;

    /**
     * @var array the bootstrap grid column css prefixes mapping, the key is the bootstrap versions, and the value is
     * an array containing the sizes and their corresponding grid column css prefixes. The class using this trait, must
     * implement BootstrapInterface. If not set will default to:
     * ```php
     * [
     *   '3' => [
     *      self::SIZE_X_SMALL => 'col-xs-',
     *      self::SIZE_SMALL => 'col-sm-',
     *      self::SIZE_MEDIUM => 'col-md-',
     *      self::SIZE_LARGE => 'col-lg-',
     *      self::SIZE_X_LARGE => 'col-lg-',
     *   ],
     *   '4' => [
     *      self::SIZE_X_SMALL => 'col-',
     *      self::SIZE_SMALL => 'col-sm-',
     *      self::SIZE_MEDIUM => 'col-md-',
     *      self::SIZE_LARGE => 'col-lg-',
     *      self::SIZE_X_LARGE => 'col-xl-',
     *   ],
     * ];
     * ```
     */
    public array $bsColCssPrefixes = [
        '4' => [
            self::SIZE_X_SMALL => 'col-',
            self::SIZE_SMALL => 'col-sm-',
            self::SIZE_MEDIUM => 'col-md-',
            self::SIZE_LARGE => 'col-lg-',
            self::SIZE_X_LARGE => 'col-xl-',
        ],
    ];

    /**
     * @var string default icon CSS prefix
     */
    protected string $_defaultIconPrefix = 'fas fa-';

    /**
     * @var string default bootstrap button CSS
     */
    protected string $_defaultBtnCss = 'btn-outline-secondary';

    /**
     * Initializes bootstrap versions for the widgets and asset bundles.
     *
     * @throws InvalidConfigException
     */
    protected function initBsVersion()
    {
        $interface = BootstrapInterface::class;
        if (!($this instanceof $interface)) {
            $class = get_called_class();
            throw new InvalidConfigException("'{$class}' must implement '{$interface}'.");
        }
    }

    /**
     * Configures the bootstrap version settings
     * @return string the bootstrap lib parsed version number
     */
    protected function configureBsVersion()
    {
        return '4';
    }

    /**
     * Validate if Bootstrap 4.x version
     * 
     * @return bool
     * @deprecated Only Bootstrap 4.x supported
     */
    public function isBs4()
    {
        return true;
    }

    /**
     * Gets the default button CSS
     * @return string
     */
    public function getDefaultBtnCss()
    {
        return $this->_defaultBtnCss;
    }

    /**
     * Gets the default icon css prefix
     * @return string
     */
    public function getDefaultIconPrefix()
    {
        return $this->_defaultIconPrefix;
    }

    /**
     * Gets bootstrap css class by parsing the bootstrap version for the specified BS CSS type
     * @param string $type the bootstrap CSS class type
     * @param bool $asString whether to return classes as a string separated by space
     * @return string
     * @throws InvalidConfigException
     */
    public function getCssClass($type, $asString = true)
    {
        if (empty(static::$bsCssMap[$type])) {
            return '';
        }
        $config = static::$bsCssMap[$type];
        $css = !empty($config) ? $config : '';
        return $asString && is_array($css) ? implode(' ', $css) : $css;
    }

    /**
     * Adds bootstrap CSS class to options by parsing the bootstrap version for the specified Bootstrap CSS type
     * @param array $options the HTML attributes for the container element that will be modified
     * @param string $type the bootstrap CSS class type
     * @return \kartik\base\Widget|mixed current object instance that uses this trait
     * @throws InvalidConfigException
     */
    public function addCssClass(&$options, $type)
    {
        $css = $this->getCssClass($type, false);
        if (!empty($css)) {
            Html::addCssClass($options, $css);
        }
        return $this;
    }

    /**
     * Removes bootstrap CSS class from options by parsing the bootstrap version for the specified Bootstrap CSS type
     * @param array $options the HTML attributes for the container element that will be modified
     * @param string $type the bootstrap CSS class type
     * @return \kartik\base\Widget|mixed current object instance that uses this trait
     * @throws InvalidConfigException
     */
    public function removeCssClass(&$options, $type)
    {
        $css = $this->getCssClass($type, false);
        if (!empty($css)) {
            Html::removeCssClass($options, $css);
        }
        return $this;
    }

    /**
     * Parses and returns the major BS version
     * @param string $ver
     * @return bool|string
     */
    protected static function parseVer($ver)
    {
        $ver = (string)$ver;
        return substr(trim($ver), 0, 1);
    }

    /**
     * Compares two versions and checks if they are of the same major BS version
     * @param int|string $ver1 first version
     * @param int|string $ver2 second version
     * @return bool whether major versions are equal
     */
    protected static function isSameVersion($ver1, $ver2)
    {
        if ($ver1 === $ver2 || (empty($ver1) && empty($ver2))) {
            return true;
        }
        return static::parseVer($ver1) === static::parseVer($ver2);
    }
}
