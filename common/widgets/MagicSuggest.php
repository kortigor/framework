<?php

declare(strict_types=1);

namespace common\widgets;

use core\activeform\InputWidget;
use core\helpers\Html;
use core\helpers\Json;

/**
 * Widget for MagicSuggest.
 * 
 * @see http://nicolasbize.com/magicsuggest/doc.html
 */
class MagicSuggest extends InputWidget
{
    /**
     * @var array Input options
     */
    public array $options = ['class' => 'form-control'];

    /**
     * @var array MagicSuggest options
     */
    public array $clientOptions = [];

    /**
     * @var array Suggested items.
     * If set, will be used as object value for MagicSuggest 'data' option.
     * Be sure to set only one of $items or $url attrubute.
     */
    public array $items;

    /**
     * @var string Url to get suggest data via ajax request.
     * If set, will be used as string value for MagicSuggest 'data' option.
     * Be sure to set only one of $items or $url attrubute.
     */
    public string $url;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        $this->registerClientScript();

        if ($this->hasModel()) {
            $hidden = Html::hiddenInput(Html::getInputName($this->model, $this->attribute), '');
            $input = Html::activeTextInput($this->model, $this->attribute, $this->options);
        } else {
            $hidden = Html::hiddenInput($this->name, '');
            $input = Html::textInput($this->name, $this->value, $this->options);
        }
        // Add hidden input to ensure field will present in form data
        echo $hidden . $input;
    }

    /**
     * Registers the needed JavaScript.
     */
    public function registerClientScript()
    {
        $view = $this->getView();
        $options = $this->clientOptions;

        if (isset($this->items)) {
            $options['data'] = $this->items;
        }

        if (isset($this->url)) {
            $options['data'] = $this->url;
        }

        // $options = empty($options) ? '' : Json::htmlEncode($options);
        $options = empty($options) ? '' : Json::encode($options);
        $id = $this->options['id'];
        MagicSuggestAsset::register($view);
        $view->registerJs("jQuery('#{$id}').magicSuggest($options);");
    }
}
