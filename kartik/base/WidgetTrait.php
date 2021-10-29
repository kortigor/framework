<?php

declare(strict_types=1);

namespace kartik\base;

use core\helpers\ArrayHelper;
use core\helpers\Json;
use core\activeform\JsExpression;
use core\web\View;
use InvalidArgumentException;

/**
 * WidgetTrait manages all methods used by Krajee widgets and input widgets.
 *
 * @property array $options
 *
 * @method View getView()
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 */
trait WidgetTrait
{
    use BootstrapTrait;

    /**
     * @var boolean prevent duplication of pjax containers when browser back & forward buttons are pressed.
     * - If this property is not set.
     * - If is not set, then this property will default to `true`.
     */
    public bool $pjaxDuplicationFix = true;

    /**
     * @var boolean enable pop state fix for pjax container on press of browser back & forward buttons.
     * @deprecated DEPRECATED since v2.0.5 and replaced with [[pjaxDuplicationFix]]
     */
    public bool $enablePopStateFix = false;

    /**
     * @var string the plugin name
     */
    public string $pluginName = '';

    /**
     * @var string the javascript that will be used to destroy the jQuery plugin
     */
    public string $pluginDestroyJs;

    /**
     * @var array widget JQuery events. You must define events in `event-name => event-function` format. For example:
     *
     * ~~~
     * pluginEvents = [
     *     'change' => 'function() { log("change"); }',
     *     'open' => 'function() { log("open"); }',
     * ];
     * ~~~
     */
    public array $pluginEvents = [];

    /**
     * @var array widget plugin options.
     */
    public array $pluginOptions = [];

    /**
     * @var array widget plugin options.
     */
    public array $defaultPluginOptions = [];

    /**
     * @var array default HTML attributes or other settings for widgets.
     */
    public array $defaultOptions = [];

    /**
     * @var string the identifier for the PJAX widget container if the editable widget is to be rendered inside a PJAX
     * container. This will ensure the PopoverX plugin is initialized correctly after a PJAX request is completed.
     * If this is not set, no re-initialization will be done for pjax.
     */
    public string $pjaxContainerId;

    /**
     * @var integer the position where the client JS hash variables for the input widget will be loaded.
     * Defaults to `View::POS_HEAD`. This can be set to `View::POS_READY` for specific scenarios like when
     * rendering the widget via `renderAjax`.
     */
    public int $hashVarLoadPosition = View::POS_HEAD;

    /**
     * @var string the generated hashed variable name that will store the JSON encoded pluginOptions in
     * [[View::POS_HEAD]].
     */
    protected string $_hashVar;

    /**
     * @var string the JSON encoded plugin options.
     */
    protected string $_encOptions = '';

    /**
     * @var string the HTML5 data variable name that will be used to store the Json encoded pluginOptions within the
     * element on which the jQuery plugin will be initialized.
     */
    protected string $_dataVar;

    /**
     * Sets a HTML5 data variable.
     *
     * @param string $name the plugin name
     */
    protected function setDataVar(string $name): void
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $this->_dataVar = "data-krajee-{$name}";
    }

    /**
     * Merge default options
     */
    protected function mergeDefaultOptions(): void
    {
        $this->pluginOptions = ArrayHelper::merge($this->defaultPluginOptions, $this->pluginOptions);
        $this->options = ArrayHelper::merge($this->defaultOptions, $this->options);
    }

    /**
     * Generates the `pluginDestroyJs` script if it is not set.
     */
    protected function initDestroyJs(): void
    {
        if (isset($this->pluginDestroyJs)) {
            return;
        }
        if (empty($this->pluginName)) {
            $this->pluginDestroyJs = '';
            return;
        }
        $id = "jQuery('#" . $this->options['id'] . "')";
        $plugin = $this->pluginName;
        $this->pluginDestroyJs = "if ({$id}.data('{$this->pluginName}')) { {$id}.{$plugin}('destroy'); }";
    }

    /**
     * Adds an asset to the view.
     *
     * @param View $view the View object
     * @param string $file Asset file name
     * @param string $type Asset file type (css or js)
     * @param string $class Class name of the AssetBundle
     * 
     * @throws InvalidArgumentException If AssetBundle given in $class not registered.
     */
    protected function addAsset(View $view, string $file, string $type, string $class): void
    {
        if (!in_array($type, ['js', 'css'])) {
            return;
        }

        if (!isset($view->assetBundles[$class])) {
            throw new InvalidArgumentException("Unable to add asset to bundle. AssetBundle {$class} not registered");
        }

        $view->assetBundles[$class]->$type[] = $file;
    }

    /**
     * Generates a hashed variable to store the pluginOptions. The following special data attributes will also be setup
     * for the input widget, that can be accessed through javascript :
     *
     * - 'data-krajee-{name}' will store the hashed variable storing the plugin options. The `{name}` token will be
     *   replaced with the plugin name (e.g. `select2`, ``typeahead etc.). This fixes
     *   [issue #6](https://github.com/kartik-v/yii2-krajee-base/issues/6).
     *
     * @param string $name the name of the plugin
     */
    protected function hashPluginOptions(string $name): void
    {
        $this->_encOptions = empty($this->pluginOptions) ? '' : Json::htmlEncode($this->pluginOptions);
        $this->_hashVar = $name . '_' . hash('crc32', $this->_encOptions);
        $this->options['data-krajee-' . $name] = $this->_hashVar;
    }

    /**
     * Registers plugin options by storing within a uniquely generated javascript variable.
     *
     * @param string $name the plugin name
     */
    protected function registerPluginOptions(string $name): void
    {
        $this->hashPluginOptions($name);
        $encOptions = empty($this->_encOptions) ? '{}' : $this->_encOptions;
        $this->registerWidgetJs("window.{$this->_hashVar} = {$encOptions};\n", $this->hashVarLoadPosition);
    }

    /**
     * Returns the plugin registration script.
     *
     * @param string $name the name of the plugin
     * @param string $element the plugin target element
     * @param string $callback the javascript callback function to be called after plugin loads
     * @param string $callbackCon the javascript callback function to be passed to the plugin constructor
     *
     * @return string the generated plugin script
     */
    protected function getPluginScript(string $name, string $element = '', string $callback = '', string $callbackCon = ''): string
    {
        $id = $element ? $element : "jQuery('#" . $this->options['id'] . "')";
        $script = '';
        if ($this->pluginOptions !== false) {
            $this->registerPluginOptions($name);
            $script = "{$id}.{$name}({$this->_hashVar})";
            if ($callbackCon) {
                $script = "{$id}.{$name}({$this->_hashVar}, {$callbackCon})";
            }
            if ($callback) {
                $script = "jQuery.when({$script}).done({$callback})";
            }
            $script .= ";\n";
        }
        $script = $this->pluginDestroyJs . "\n" . $script;
        if (!empty($this->pluginEvents)) {
            foreach ($this->pluginEvents as $event => $handler) {
                $function = $handler instanceof JsExpression ? $handler : new JsExpression($handler);
                $script .= "{$id}.on('{$event}', {$function});\n";
            }
        }
        return $script;
    }

    /**
     * Registers a specific plugin and the related events
     *
     * @param string $name the name of the plugin
     * @param string $element the plugin target element
     * @param string $callback the javascript callback function to be called after plugin loads
     * @param string $callbackCon the javascript callback function to be passed to the plugin constructor
     */
    protected function registerPlugin(string $name, string $element = '', string $callback = '', string $callbackCon = ''): void
    {
        $script = $this->getPluginScript($name, $element, $callback, $callbackCon);
        $this->registerWidgetJs($script);
    }

    /**
     * Fix for weird PJAX container duplication behavior on pressing browser back and forward buttons.
     * @param View $view
     */
    protected function fixPjaxDuplication(View $view): void
    {
        if ($this->pjaxDuplicationFix === true) {
            $view->registerJs('jQuery&&jQuery.pjax&&(jQuery.pjax.defaults.maxCacheLength=0);');
        }
    }

    /**
     * Registers a JS code block for the widget.
     *
     * @param string $js the JS code block to be registered
     * @param int $pos the position at which the JS script tag should be inserted in a page. The possible values
     * are:
     * - [[View::POS_HEAD]]: in the head section
     * - [[View::POS_BEGIN]]: at the beginning of the body section
     * - [[View::POS_END]]: at the end of the body section
     * - [[View::POS_LOAD]]: enclosed within jQuery(window).load(). Note that by using this position, the method will
     *   automatically register the jQuery js file.
     * - [[View::POS_READY]]: enclosed within jQuery(document).ready(). This is the default value. Note that by using
     *   this position, the method will automatically register the jQuery js file.
     * @param string $key the key that identifies the JS code block. If empty, it will use `$js` as the key. If two JS
     * code blocks are registered with the same key, the latter will overwrite the former.
     */
    public function registerWidgetJs(string $js, int $pos = View::POS_READY, string $key = ''): void
    {
        $view = $this->getView();
        WidgetAsset::register($view);
        $this->fixPjaxDuplication($view);
        if (empty($js)) {
            return;
        }
        $view->registerJs($js, $pos, $key);
        if (!empty($this->pjaxContainerId) && ($pos === View::POS_LOAD || $pos === View::POS_READY)) {
            $pjax = 'jQuery("#' . $this->pjaxContainerId . '")';
            $evComplete = 'pjax:complete.' . hash('crc32', $js);
            $script = "setTimeout(function(){ {$js} }, 100);";
            $view->registerJs("{$pjax}.off('{$evComplete}').on('{$evComplete}',function(){ {$script} });");
        }
    }
}