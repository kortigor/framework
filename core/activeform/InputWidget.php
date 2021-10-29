<?php

declare(strict_types=1);

namespace core\activeform;

use Exception;
use core\base\Model;
use core\widgets\Widget;
use core\helpers\Html;

/**
 * InputWidget is the base class for widgets that collect user inputs.
 *
 * An input widget can be associated with a data [[model]] and an [[attribute]],
 * or a [[name]] and a [[value]]. If the former, the name and the value will
 * be generated automatically (subclasses may call [[renderInputHtml()]] to follow this behavior).
 *
 * Classes extending from this widget can be used in an [[\core\activeform\ActiveForm|ActiveForm]]
 * using the [[\core\activeform\ActiveField::widget()|widget()]] method, for example like this:
 *
 * ```
 * $form->field($model, 'from_date')->widget('WidgetClassName', [
 *     // configure additional widget properties here
 * ])
 * ```
 *
 * For more details and usage information on InputWidget, see the [guide article on forms](guide:input-forms).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 */
class InputWidget extends Widget
{
    /**
     * @var \core\activeform\ActiveField active input field, which triggers this widget rendering.
     * This field will be automatically filled up in case widget instance is created via \core\activeform\ActiveField::widget().
     */
    public ActiveField $field;
    /**
     * @var Model the data model that this widget is associated with.
     */
    public Model $model;
    /**
     * @var string the model attribute that this widget is associated with.
     */
    public string $attribute;
    /**
     * @var string the input name. This must be set if [[model]] and [[attribute]] are not set.
     */
    public string $name;
    /**
     * @var mixed the input value.
     */
    public mixed $value = null;
    /**
     * @var array the HTML attributes for the input tag.
     * @see \core\activeform\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $options = [];


    /**
     * Initializes the widget.
     * If you override this method, make sure you call the parent implementation first.
     */
    public function init()
    {
        if (!isset($this->name) && !$this->hasModel()) {
            throw new Exception("Either 'name', or 'model' and 'attribute' properties must be specified.");
        }
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->getId();
        }
        parent::init();
    }

    /**
     * @return bool whether this widget is associated with a data model.
     */
    protected function hasModel(): bool
    {
        return isset($this->model, $this->attribute);
    }

    /**
     * Render a HTML input tag.
     *
     * This will call [[Html::activeInput()]] if the input widget is [[hasModel()|tied to a model]],
     * or [[Html::input()]] if not.
     *
     * @param string $type the type of the input to create.
     * @return string the HTML of the input field.
     * @see Html::activeInput()
     * @see Html::input()
     */
    protected function renderInputHtml($type): string
    {
        if ($this->hasModel()) {
            return Html::activeInput($type, $this->model, $this->attribute, $this->options);
        }
        return Html::input($type, $this->name, $this->value, $this->options);
    }
}