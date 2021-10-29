<?php

declare(strict_types=1);

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014 - 2020
 * @version 1.1.0
 */

namespace kartik\file;

use core\helpers\ArrayHelper;
use core\helpers\Html;
use kartik\base\InputWidget;

/**
 * Wrapper for the Bootstrap FileInput JQuery Plugin by Krajee. The FileInput widget is styled for Bootstrap 4.x
 * with ability to multiple file selection and preview, format button styles and inputs. Runs on all modern
 * browsers supporting HTML5 File Inputs and File Processing API. For browser versions IE9 and below, this widget
 * will gracefully degrade to a native HTML file input.
 *
 * @see http://plugins.krajee.com/bootstrap-fileinput
 * @see https://github.com/kartik-v/bootstrap-fileinput
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @see http://twitter.github.com/typeahead.js/examples
 */
class FileInput extends InputWidget
{
    /**
     * @var bool whether to resize images on client side
     */
    public bool $resizeImages = false;

    /**
     * @var bool whether to auto orient images on client side
     */
    public bool $autoOrientImages = true;

    /**
     * @var bool whether to load sortable plugin to rearrange initial preview images on client side
     */
    public bool $sortThumbs = true;

    /**
     * @var bool whether to load dom purify plugin to purify HTML content in purfiy
     * @deprecated since v1.1.0 (not required since bootstrap-fileinput v5.1.3)
     */
    public bool $purifyHtml = true;

    /**
     * @var bool whether to show 'plugin unsupported' message for IE browser versions 9 & below
     */
    public bool $showMessage = true;

    /*
     * @var array HTML attributes for the container for the warning
     * message for browsers running IE9 and below.
     */
    public array $messageOptions = ['class' => 'alert alert-warning'];

    /**
     * @inheritdoc
     */
    public string $pluginName = 'fileinput';

    /**
     * @var array the list of inbuilt themes
     */
    protected static $_themes = ['fa', 'fas', 'gly', 'explorer', 'explorer-fa', 'explorer-fas'];

    /**
     * @inheritdoc
     * @throws \ReflectionException
     * @throws \core\exception\InvalidConfigException
     */
    public function run()
    {
        return $this->initWidget();
    }

    /**
     * Initializes widget
     * @throws \ReflectionException
     * @throws \core\exception\InvalidConfigException
     */
    protected function initWidget()
    {
        $this->initLanguage();
        $this->registerAssets();
        if ($this->pluginLoading) {
            Html::addCssClass($this->options, 'file-loading');
        }
        /**
         * Auto-set form enctype for file uploads
         */
        if (isset($this->field) && isset($this->field->form) && !isset($this->field->form->options['enctype'])) {
            $this->field->form->options['enctype'] = 'multipart/form-data';
        }
        /**
         * Auto-set multiple file upload naming convention
         */
        if (ArrayHelper::getValue($this->options, 'multiple') && !ArrayHelper::getValue($this->pluginOptions, 'uploadUrl')) {
            $hasModel = $this->hasModel();
            if ($hasModel && strpos($this->attribute, '[]') === false) {
                $this->attribute .= '[]';
            } elseif (!$hasModel && strpos($this->name, '[]') === false) {
                $this->name .= '[]';
            }
        }
        $input = $this->getInput('fileInput');
        $script = 'document.getElementById("' . $this->options['id'] . '").className.replace(/\bfile-loading\b/,"");';

        if ($this->showMessage) {
            $validation = ArrayHelper::getValue($this->pluginOptions, 'showPreview', true)
                ? t('file preview and multiple file upload')
                : t('multiple file upload');

            $message = '<strong>' . t('fileinput', 'Note:') . '</strong> ' .
                t("Your browser does not support {$validation}. Try an alternative or more recent browser to access these features.");
            $content = Html::tag('div', $message, $this->messageOptions) . "<script>{$script};</script>";
            $input .= "\n" . $this->validateIE($content);
        }

        return $input;
    }

    /**
     * Validates and returns content based on IE browser version validation
     *
     * @param string $content
     * @param string $validation
     *
     * @return string
     */
    protected function validateIE($content, $validation = 'lt IE 10')
    {
        return "<!--[if {$validation}]><br>{$content}<![endif]-->";
    }

    /**
     * Registers the asset bundle and locale
     * @throws \core\exception\InvalidConfigException
     */
    public function registerAssetBundle()
    {
        $view = $this->getView();
        $this->pluginOptions['resizeImage'] = $this->resizeImages;
        $this->pluginOptions['autoOrientImage'] = $this->autoOrientImages;
        if ($this->resizeImages || $this->autoOrientImages) {
            PiExifAsset::register($view);
        }
        if (empty($this->pluginOptions['theme'])) {
            $this->pluginOptions['theme'] = 'fas';
        }
        $theme = ArrayHelper::getValue($this->pluginOptions, 'theme');
        if (!empty($theme) && in_array($theme, self::$_themes)) {
            FileInputThemeAsset::register($view)->addTheme($theme);
        }
        if ($this->sortThumbs) {
            SortableAsset::register($view);
        }
        FileInputAsset::register($view)->addLanguage($this->language, '', 'js/locales');
    }

    /**
     * Registers the needed assets
     * @throws \core\exception\InvalidConfigException
     */
    public function registerAssets()
    {
        $this->registerAssetBundle();
        $this->registerPlugin($this->pluginName);
    }
}