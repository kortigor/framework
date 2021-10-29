<?php

declare(strict_types=1);

namespace core\widgets;

use core\helpers\{
    ArrayHelper,
    Html,
    Json,
};

/**
 * Pjax is a widget integrating the [pjax](https://github.com/yiisoft/jquery-pjax) jQuery plugin.
 *
 * Pjax only deals with the content enclosed between its [[begin()]] and [[end()]] calls, called the *body content* of the widget.
 * By default, any link click or form submission (for those forms with `data-pjax` attribute) within the body content
 * will trigger an AJAX request. In responding to the AJAX request, Pjax will send the updated body content (based
 * on the AJAX request) to the client which will replace the old content with the new one. The browser's URL will then
 * be updated using pushState. The whole process requires no reloading of the layout or resources (js, css).
 *
 * You may configure [[linkSelector]] to specify which links should trigger pjax, and configure [[formSelector]]
 * to specify which form submission may trigger pjax.
 *
 * You may disable pjax for a specific link inside the container by adding `data-pjax="0"` attribute to this link.
 *
 * The following example shows how to use Pjax with the [[\grid\GridView]] widget so that the grid pagination,
 * sorting and filtering can be done via pjax:
 *
 * ```php
 * use core\activeform\Pjax;
 *
 * Pjax::begin();
 * echo GridView::widget([...]);
 * Pjax::end();
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com> & Kort
 */
class Pjax extends Widget
{
    /**
     * @var array the HTML attributes for the widget container tag. The following special options are recognized:
     *
     * - `tag`: string, the tag name for the container. Defaults to `div`
     *   See also [[\core\helpers\Html::tag()]].
     *
     * @see \core\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $options = [];
    /**
     * @var string|false the jQuery selector of the links that should trigger pjax requests.
     * If not set, all links within the enclosed content of Pjax will trigger pjax requests.
     * If set to false, no code will be registered to handle links.
     * Note that if the response to the pjax request is a full page, a normal request will be sent again.
     */
    public $linkSelector;
    /**
     * @var string|false the jQuery selector of the forms whose submissions should trigger pjax requests.
     * If not set, all forms with `data-pjax` attribute within the enclosed content of Pjax will trigger pjax requests.
     * If set to false, no code will be registered to handle forms.
     * Note that if the response to the pjax request is a full page, a normal request will be sent again.
     */
    public $formSelector;
    /**
     * @var string The jQuery event that will trigger form handler. Defaults to "submit".
     */
    public string $submitEvent = 'submit';
    /**
     * @var bool whether to enable push state.
     */
    public bool $enablePushState = true;
    /**
     * @var bool whether to enable replace state.
     */
    public bool $enableReplaceState = false;
    /**
     * @var int pjax timeout setting (in milliseconds). This timeout is used when making AJAX requests.
     * Use a bigger number if your server is slow. If the server does not respond within the timeout,
     * a full page load will be triggered.
     */
    public int $timeout = 1200;
    /**
     * @var bool|int how to scroll the page when pjax response is received. If false, no page scroll will be made.
     * Use a number if you want to scroll to a particular place.
     */
    public bool $scrollTo = false;
    /**
     * @var array additional options to be passed to the pjax JS plugin. Please refer to the
     * [pjax project page](https://github.com/yiisoft/jquery-pjax) for available options.
     */
    public array $clientOptions = [];
    /**
     * {@inheritdoc}
     * @internal
     */
    public static int $counter = 0;
    /**
     * {@inheritdoc}
     */
    public static string $autoIdPrefix = 'p';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }

        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'div');
        echo Html::beginTag($tag, array_merge([
            'data-pjax-container' => '',
            'data-pjax-push-state' => json_encode($this->enablePushState),
            'data-pjax-replace-state' => $this->enableReplaceState,
            'data-pjax-timeout' => $this->timeout,
            'data-pjax-scrollto' => $this->scrollTo,
        ], $options));
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        echo Html::endTag(ArrayHelper::remove($this->options, 'tag', 'div'));
        $this->registerClientScript();
    }

    /**
     * Registers the needed JavaScript.
     */
    public function registerClientScript()
    {
        $id = $this->options['id'];
        $this->clientOptions['push'] = $this->enablePushState;
        $this->clientOptions['replace'] = $this->enableReplaceState;
        $this->clientOptions['timeout'] = $this->timeout;
        $this->clientOptions['scrollTo'] = $this->scrollTo;
        if (!isset($this->clientOptions['container'])) {
            $this->clientOptions['container'] = '#' . $id;
        }
        $options = Json::htmlEncode($this->clientOptions);
        $js = '';
        if ($this->linkSelector !== false) {
            $linkSelector = Json::htmlEncode($this->linkSelector !== null ? $this->linkSelector : '#' . $id . ' a');
            $js .= "jQuery(document).pjax($linkSelector, $options);";
        }
        if ($this->formSelector !== false) {
            $formSelector = Json::htmlEncode($this->formSelector !== null ? $this->formSelector : '#' . $id . ' form[data-pjax]');
            $submitEvent = Json::htmlEncode($this->submitEvent);
            $js .= "\njQuery(document).off($submitEvent, $formSelector).on($submitEvent, $formSelector, function (event) {jQuery.pjax.submit(event, $options);});";
        }
        $view = $this->getView();
        PjaxAsset::register($view);

        if ($js !== '') {
            $view->registerJs($js);
        }
    }
}
