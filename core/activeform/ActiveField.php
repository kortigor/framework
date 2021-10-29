<?php

declare(strict_types=1);

namespace core\activeform;

use Throwable;
use core\base\Model;
use core\helpers\ArrayHelper;
use core\helpers\Html;

/**
 * ActiveField represents a form input field within an [[ActiveForm]].
 */
class ActiveField
{
    /**
     * @var ActiveForm the form that this field is associated with.
     */
    public ActiveForm $form;
    /**
     * @var Model the data model that this field is associated with.
     */
    public Model $model;
    /**
     * @var string the model attribute that this field is associated with.
     */
    public string $attribute;
    /**
     * @var array the HTML attributes (name-value pairs) for the field container tag.
     * The values will be HTML-encoded using [[Html::encode()]].
     * If a value is `null`, the corresponding attribute will not be rendered.
     * The following special options are recognized:
     *
     * - `tag`: the tag name of the container element. Defaults to `div`. Setting it to `false` will not render a container tag.
     *   See also [[\core\helpers\Html::tag()]].
     *
     * If you set a custom `id` for the container element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @see \core\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $options = ['class' => 'form-group'];
    /**
     * @var string the template that is used to arrange the label, the input field, the error message and the hint text.
     * The following tokens will be replaced when [[render()]] is called: `{label}`, `{input}`, `{error}` and `{hint}`.
     */
    public string $template = "{label}\n{input}\n{hint}\n{error}";
    /**
     * @var array the default options for the input tags. The parameter passed to individual input methods
     * (e.g. [[textInput()]]) will be merged with this property when rendering the input tag.
     *
     * If you set a custom `id` for the input element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @see \core\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $inputOptions = ['class' => 'form-control'];
    /**
     * @var array the default options for the error tags. The parameter passed to [[error()]] will be
     * merged with this property when rendering the error tag.
     * The following special options are recognized:
     *
     * - `tag`: the tag name of the container element. Defaults to `div`. Setting it to `false` will not render a container tag.
     *   See also [[\core\helpers\Html::tag()]].
     * - `encode`: whether to encode the error output. Defaults to `true`.
     *
     * If you set a custom `id` for the error element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @see \core\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $errorOptions = ['class' => 'help-block'];
    /**
     * @var array the default options for the label tags. The parameter passed to [[label()]] will be
     * merged with this property when rendering the label tag.
     * @see \core\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $labelOptions = ['class' => 'control-label'];
    /**
     * @var array the default options for the hint tags. The parameter passed to [[hint()]] will be
     * merged with this property when rendering the hint tag.
     * The following special options are recognized:
     *
     * - `tag`: the tag name of the container element. Defaults to `div`. Setting it to `false` will not render a container tag.
     *   See also [[\core\helpers\Html::tag()]].
     *
     * @see \core\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $hintOptions = ['class' => 'hint-block'];
    /**
     * @var bool whether to enable client-side data validation.
     * If not set, it will take the value of [[ActiveForm::enableClientValidation]].
     */
    public bool $enableClientValidation;
    /**
     * @var bool whether to enable AJAX-based data validation.
     * If not set, it will take the value of [[ActiveForm::enableAjaxValidation]].
     */
    public bool $enableAjaxValidation;
    /**
     * @var array the jQuery selectors for selecting the container, input and error tags.
     * The array keys should be `container`, `input`, and/or `error`, and the array values
     * are the corresponding selectors. For example, `['input' => '#my-input']`.
     *
     * The container selector is used under the context of the form, while the input and the error
     * selectors are used under the context of the container.
     *
     * You normally do not need to set this property as the default selectors should work well for most cases.
     */
    public array $selectors = [];
    /**
     * @var array different parts of the field (e.g. input, label). This will be used together with
     * [[template]] to generate the final field HTML code. The keys are the token names in [[template]],
     * while the values are the corresponding HTML code. Valid tokens include `{input}`, `{label}` and `{error}`.
     * Note that you normally don't need to access this property directly as
     * it is maintained by various methods of this class.
     */
    public array $parts = [];
    /**
     * @var bool adds aria HTML attributes `aria-required` and `aria-invalid` for inputs
     */
    public bool $addAriaAttributes = true;
    /**
     * @var bool whether to render field.
     */
    public bool $visible = true;

    /**
     * @var string this property holds a custom input id if it was set using [[inputOptions]] or in one of the
     * `$options` parameters of the `input*` methods.
     */
    private string $_inputId;
    /**
     * @var bool if "for" field label attribute should be skipped.
     */
    private bool $_skipLabelFor = false;

    /**
     * Constructor.
     * 
     * @param array $config Array of object properties values.
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $attribute => $value) {
            $this->$attribute = $value;
        }
        $this->init();
    }

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     */
    public function init()
    {
    }

    /**
     * PHP magic method that returns the string representation of this object.
     * @return string the string representation of this object.
     */
    public function __toString()
    {
        // __toString cannot throw exception
        // use trigger_error to bypass this limitation
        try {
            return $this->render();
        } catch (Throwable $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
            return '';
        }
    }

    /**
     * Renders the whole field.
     * This method will generate the label, error tag, input tag and hint tag (if any), and
     * assemble them into HTML according to [[template]].
     * @param string|callable $content the content within the field container.
     * If `null` (not set), the default methods will be called to generate the label, error tag and input tag,
     * and use them as the content.
     * If a callable, it will be called to generate the content. The signature of the callable should be:
     *
     * ```php
     * function ($field) {
     *     return $html;
     * }
     * ```
     *
     * @return string the rendering result.
     */
    public function render($content = null): string
    {
        if (!$this->visible) {
            return '';
        }

        if ($content === null) {
            if (!isset($this->parts['{input}'])) {
                $this->textInput();
            }
            if (!isset($this->parts['{label}'])) {
                $this->label();
            }
            if (!isset($this->parts['{error}'])) {
                $this->error();
            }
            if (!isset($this->parts['{hint}'])) {
                $this->hint(null);
            }
            $content = strtr($this->template, $this->parts);
        } elseif (!is_string($content)) {
            $content = call_user_func($content, $this);
        }

        return $this->begin() . "\n" . $content . "\n" . $this->end();
    }

    /**
     * Renders the opening tag of the field container.
     * @return string the rendering result.
     */
    public function begin(): string
    {
        if ($this->form->enableClientScript) {
            $clientOptions = $this->getClientOptions();
            if (!empty($clientOptions)) {
                $this->form->attributes[] = $clientOptions;
            }
        }

        $inputID = $this->getInputId();
        $attribute = Html::getAttributeName($this->attribute);
        $options = $this->options;
        $class = isset($options['class']) ? (array) $options['class'] : [];
        $class[] = "field-$inputID";
        if ($this->model->isAttributeRequired($attribute)) {
            $class[] = $this->form->requiredCssClass;
        }
        $options['class'] = implode(' ', $class);
        if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_CONTAINER) {
            $this->addErrorClassIfNeeded($options);
        }
        $tag = ArrayHelper::remove($options, 'tag', 'div');

        return Html::beginTag($tag, $options);
    }

    /**
     * Renders the closing tag of the field container.
     * @return string the rendering result.
     */
    public function end(): string
    {
        return Html::endTag(ArrayHelper::keyExists('tag', $this->options) ? $this->options['tag'] : 'div');
    }

    /**
     * Generates a label tag for [[attribute]].
     * @param string|bool|null $label the label to use. If `null`, the label will be generated via [[Model::getAttributeLabel()]].
     * If `false`, the generated field will not contain the label part.
     * Note that this will NOT be [[Html::encode()|encoded]].
     * @param array $options the tag options in terms of name-value pairs. It will be merged with [[labelOptions]].
     * The options will be rendered as the attributes of the resulting tag. The values will be HTML-encoded
     * using [[Html::encode()]]. If a value is `null`, the corresponding attribute will not be rendered.
     * @return $this the field object itself.
     */
    public function label(string|bool $label = null, array $options = []): static
    {
        if (is_bool($label) && $label === false) {
            $this->parts['{label}'] = '';
            return $this;
        }

        $options = array_merge($this->labelOptions, $options);
        if ($label !== null) {
            $options['label'] = (string) $label;
        }

        if ($this->_skipLabelFor) {
            $options['for'] = null;
        }

        $this->parts['{label}'] = Html::activeLabel($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Returns the HTML `id` of the input element of this form field.
     * @return string the input id.
     */
    public function getInputId(): string
    {
        return $this->_inputId ?? Html::getInputId($this->model, $this->attribute);
    }

    /**
     * Generates a tag that contains the first validation error of [[attribute]].
     * Note that even if there is no validation error, this method will still return an empty error tag.
     * @param array|false $options the tag options in terms of name-value pairs. It will be merged with [[errorOptions]].
     * The options will be rendered as the attributes of the resulting tag. The values will be HTML-encoded
     * using [[Html::encode()]]. If this parameter is `false`, no error tag will be rendered.
     *
     * The following options are specially handled:
     *
     * - `tag`: this specifies the tag name. If not set, `div` will be used.
     *   See also [[\core\helpers\Html::tag()]].
     *
     * If you set a custom `id` for the error element, you may need to adjust the [[$selectors]] accordingly.
     * @see $errorOptions
     * @return $this the field object itself.
     */
    public function error(array|bool $options = []): static
    {
        if ($options === false) {
            $this->parts['{error}'] = '';
            return $this;
        }
        $options = array_merge($this->errorOptions, $options);
        $this->parts['{error}'] = Html::error($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Renders the hint tag.
     * @param string|bool $content the hint content.
     * If `null`, the hint will be generated via [[Model::getAttributeHint()]].
     * If `false`, the generated field will not contain the hint part.
     * Note that this will NOT be [[Html::encode()|encoded]].
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the hint tag. The values will be HTML-encoded using [[Html::encode()]].
     *
     * The following options are specially handled:
     *
     * - `tag`: this specifies the tag name. If not set, `div` will be used.
     *   See also [[\core\helpers\Html::tag()]].
     *
     * @return $this the field object itself.
     */
    public function hint($content, array $options = []): static
    {
        if ($content === false) {
            $this->parts['{hint}'] = '';
            return $this;
        }

        $options = array_merge($this->hintOptions, $options);
        if ($content !== null) {
            $options['hint'] = $content;
        }
        $this->parts['{hint}'] = Html::activeHint($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Renders an input tag.
     * @param string $type the input type (e.g. `text`, `password`)
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[Html::encode()]].
     *
     * If you set a custom `id` for the input element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @return $this the field object itself.
     */
    public function input($type, array $options = []): static
    {
        $options = array_merge($this->inputOptions, $options);
        if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->addErrorClassIfNeeded($options);
        }

        $this->addAriaAttributes($options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeInput($type, $this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Renders a text input.
     * This method will generate the `name` and `value` tag attributes automatically for the model attribute
     * unless they are explicitly specified in `$options`.
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[Html::encode()]].
     *
     * The following special options are recognized:
     *
     * - `maxlength`: int
     *
     * Note that if you set a custom `id` for the input element, you may need to adjust the value of [[selectors]] accordingly.
     *
     * @return $this the field object itself.
     */
    public function textInput(array $options = []): static
    {
        $options = array_merge($this->inputOptions, $options);

        if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->addErrorClassIfNeeded($options);
        }

        $this->addAriaAttributes($options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeTextInput($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Renders a hidden input.
     *
     * Note that this method is provided for completeness. In most cases because you do not need
     * to validate a hidden input, you should not need to use this method. Instead, you should
     * use [[\core\helpers\Html::activeHiddenInput()]].
     *
     * This method will generate the `name` and `value` tag attributes automatically for the model attribute
     * unless they are explicitly specified in `$options`.
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[Html::encode()]].
     *
     * If you set a custom `id` for the input element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @return $this the field object itself.
     */
    public function hiddenInput(array $options = []): static
    {
        $options = array_merge($this->inputOptions, $options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeHiddenInput($this->model, $this->attribute, $options);
        $this->parts['{label}'] = ''; // Fix by Kort. Do not show label for hidden input ))))

        return $this;
    }

    /**
     * Renders a password input.
     * This method will generate the `name` and `value` tag attributes automatically for the model attribute
     * unless they are explicitly specified in `$options`.
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[Html::encode()]].
     *
     * If you set a custom `id` for the input element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @return $this the field object itself.
     */
    public function passwordInput(array $options = []): static
    {
        $options = array_merge($this->inputOptions, $options);

        if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->addErrorClassIfNeeded($options);
        }

        $this->addAriaAttributes($options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activePasswordInput($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Renders a file input.
     * This method will generate the `name` and `value` tag attributes automatically for the model attribute
     * unless they are explicitly specified in `$options`.
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[Html::encode()]].
     *
     * If you set a custom `id` for the input element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @return $this the field object itself.
     */
    public function fileInput(array $options = []): static
    {
        if ($this->inputOptions !== ['class' => 'form-control']) {
            $options = array_merge($this->inputOptions, $options);
        }
        if (!isset($this->form->options['enctype'])) {
            $this->form->options['enctype'] = 'multipart/form-data';
        }

        if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->addErrorClassIfNeeded($options);
        }

        $this->addAriaAttributes($options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeFileInput($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Renders a text area.
     * The model attribute value will be used as the content in the textarea.
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[Html::encode()]].
     *
     * If you set a custom `id` for the textarea element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @return $this the field object itself.
     */
    public function textarea(array $options = []): static
    {
        $options = array_merge($this->inputOptions, $options);

        if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->addErrorClassIfNeeded($options);
        }

        $this->addAriaAttributes($options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeTextarea($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Renders a radio button.
     * This method will generate the `checked` tag attribute according to the model attribute value.
     * @param array $options the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - `uncheck`: string, the value associated with the uncheck state of the radio button. If not set,
     *   it will take the default value `0`. This method will render a hidden input so that if the radio button
     *   is not checked and is submitted, the value of this attribute will still be submitted to the server
     *   via the hidden input. If you do not want any hidden input, you should explicitly set this option as `null`.
     * - `label`: string, a label displayed next to the radio button. It will NOT be HTML-encoded. Therefore you can pass
     *   in HTML code such as an image tag. If this is coming from end users, you should [[Html::encode()|encode]] it to prevent XSS attacks.
     *   When this option is specified, the radio button will be enclosed by a label tag. If you do not want any label, you should
     *   explicitly set this option as `null`.
     * - `labelOptions`: array, the HTML attributes for the label tag. This is only used when the `label` option is specified.
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will
     * be HTML-encoded using [[Html::encode()]]. If a value is `null`, the corresponding attribute will not be rendered.
     *
     * If you set a custom `id` for the input element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @param bool $enclosedByLabel whether to enclose the radio within the label.
     * If `true`, the method will still use [[template]] to layout the radio button and the error message
     * except that the radio is enclosed by the label tag.
     * @return $this the field object itself.
     */
    public function radio(array $options = [], bool $enclosedByLabel = true): static
    {
        if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->addErrorClassIfNeeded($options);
        }

        $this->addAriaAttributes($options);
        $this->adjustLabelFor($options);

        if ($enclosedByLabel) {
            $this->parts['{input}'] = Html::activeRadio($this->model, $this->attribute, $options);
            $this->parts['{label}'] = '';
        } else {
            if (isset($options['label']) && !isset($this->parts['{label}'])) {
                $this->parts['{label}'] = $options['label'];
                if (!empty($options['labelOptions'])) {
                    $this->labelOptions = $options['labelOptions'];
                }
            }
            unset($options['labelOptions']);
            $options['label'] = null;
            $this->parts['{input}'] = Html::activeRadio($this->model, $this->attribute, $options);
        }

        // if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
        //     $this->addErrorClassIfNeeded($options);
        // }

        // $this->addAriaAttributes($options);
        // $this->adjustLabelFor($options);

        return $this;
    }

    /**
     * Renders a checkbox.
     * This method will generate the `checked` tag attribute according to the model attribute value.
     * @param array $options the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - `uncheck`: string, the value associated with the uncheck state of the radio button. If not set,
     *   it will take the default value `0`. This method will render a hidden input so that if the radio button
     *   is not checked and is submitted, the value of this attribute will still be submitted to the server
     *   via the hidden input. If you do not want any hidden input, you should explicitly set this option as `null`.
     * - `label`: string, a label displayed next to the checkbox. It will NOT be HTML-encoded. Therefore you can pass
     *   in HTML code such as an image tag. If this is coming from end users, you should [[Html::encode()|encode]] it to prevent XSS attacks.
     *   When this option is specified, the checkbox will be enclosed by a label tag. If you do not want any label, you should
     *   explicitly set this option as `null`.
     * - `labelOptions`: array, the HTML attributes for the label tag. This is only used when the `label` option is specified.
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will
     * be HTML-encoded using [[Html::encode()]]. If a value is `null`, the corresponding attribute will not be rendered.
     *
     * If you set a custom `id` for the input element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @param bool $enclosedByLabel whether to enclose the checkbox within the label.
     * If `true`, the method will still use [[template]] to layout the checkbox and the error message
     * except that the checkbox is enclosed by the label tag.
     * @return $this the field object itself.
     */
    public function checkbox(array $options = [], bool $enclosedByLabel = true): static
    {
        if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->addErrorClassIfNeeded($options);
        }

        $this->addAriaAttributes($options);
        $this->adjustLabelFor($options);

        if ($enclosedByLabel) {
            $this->parts['{input}'] = Html::activeCheckbox($this->model, $this->attribute, $options);
            $this->parts['{label}'] = '';
        } else {
            if (isset($options['label']) && !isset($this->parts['{label}'])) {
                $this->parts['{label}'] = $options['label'];
                if (!empty($options['labelOptions'])) {
                    $this->labelOptions = $options['labelOptions'];
                }
            }
            unset($options['labelOptions']);
            $options['label'] = null;
            $this->parts['{input}'] = Html::activeCheckbox($this->model, $this->attribute, $options);
        }

        return $this;
    }

    /**
     * Renders a drop-down list.
     * The selection of the drop-down list is taken from the value of the model attribute.
     * @param array $items the option data items. The array keys are option values, and the array values
     * are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     * For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
     * If you have a list of data models, you may convert them into the format described above using
     * [[ArrayHelper::map()]].
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
     * the labels will also be HTML-encoded.
     * @param array $options the tag options in terms of name-value pairs.
     *
     * For the list of available options please refer to the `$options` parameter of [[\core\helpers\Html::activeDropDownList()]].
     *
     * If you set a custom `id` for the input element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @return $this the field object itself.
     */
    public function dropDownList(array $items, array $options = []): static
    {
        $options = array_merge($this->inputOptions, $options);

        if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->addErrorClassIfNeeded($options);
        }

        $this->addAriaAttributes($options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeDropDownList($this->model, $this->attribute, $items, $options);

        return $this;
    }

    /**
     * Renders a list box.
     * The selection of the list box is taken from the value of the model attribute.
     * @param array $items the option data items. The array keys are option values, and the array values
     * are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     * For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
     * If you have a list of data models, you may convert them into the format described above using
     * [[\core\activeform\ArrayHelper::map()]].
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
     * the labels will also be HTML-encoded.
     * @param array $options the tag options in terms of name-value pairs.
     *
     * For the list of available options please refer to the `$options` parameter of [[\core\helpers\Html::activeListBox()]].
     *
     * If you set a custom `id` for the input element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @return $this the field object itself.
     */
    public function listBox(array $items, array $options = []): static
    {
        $options = array_merge($this->inputOptions, $options);

        if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->addErrorClassIfNeeded($options);
        }

        $this->addAriaAttributes($options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeListBox($this->model, $this->attribute, $items, $options);

        return $this;
    }

    /**
     * Renders a list of checkboxes.
     * A checkbox list allows multiple selection, like [[listBox()]].
     * As a result, the corresponding submitted value is an array.
     * The selection of the checkbox list is taken from the value of the model attribute.
     * @param array $items the data item used to generate the checkboxes.
     * The array values are the labels, while the array keys are the corresponding checkbox values.
     * @param array $options options (name => config) for the checkbox list.
     * For the list of available options please refer to the `$options` parameter of [[\core\helpers\Html::activeCheckboxList()]].
     * @return $this the field object itself.
     */
    public function checkboxList(array $items, array $options = []): static
    {
        if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->addErrorClassIfNeeded($options);
        }

        $this->addAriaAttributes($options);
        $this->adjustLabelFor($options);
        $this->_skipLabelFor = true;
        $this->parts['{input}'] = Html::activeCheckboxList($this->model, $this->attribute, $items, $options);

        return $this;
    }

    /**
     * Renders a list of radio buttons.
     * A radio button list is like a checkbox list, except that it only allows single selection.
     * The selection of the radio buttons is taken from the value of the model attribute.
     * @param array $items the data item used to generate the radio buttons.
     * The array values are the labels, while the array keys are the corresponding radio values.
     * @param array $options options (name => config) for the radio button list.
     * For the list of available options please refer to the `$options` parameter of [[\core\helpers\Html::activeRadioList()]].
     * @return $this the field object itself.
     */
    public function radioList(array $items, array $options = []): static
    {
        if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->addErrorClassIfNeeded($options);
        }

        $this->addRoleAttributes($options, 'radiogroup');
        $this->addAriaAttributes($options);
        $this->adjustLabelFor($options);
        $this->_skipLabelFor = true;
        $this->parts['{input}'] = Html::activeRadioList($this->model, $this->attribute, $items, $options);

        return $this;
    }

    /**
     * Renders a widget as the input of the field.
     *
     * Note that the widget must have both `model` and `attribute` properties. They will
     * be initialized with [[model]] and [[attribute]] of this field, respectively.
     *
     * If you want to use a widget that does not have `model` and `attribute` properties,
     * please use [[render()]] instead.
     *
     * While widgets extending from [[Widget]] work with active field, it is preferred to use
     * [[InputWidget]] as a base class.
     *
     * For example to use the [[MaskedInput]] widget to get some date input, you can use
     * the following code, assuming that `$form` is your [[ActiveForm]] instance:
     *
     * ```php
     * $form->field($model, 'date')->widget(\core\activeform\MaskedInput::className(), [
     *     'mask' => '99/99/9999',
     * ]);
     * ```
     *
     * If you set a custom `id` for the input element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @param string $class the widget class name.
     * @param array $config name-value pairs that will be used to initialize the widget.
     * @return $this the field object itself.
     * @throws \Exception
     */
    public function widget(string $class, array $config = []): static
    {
        /** @var \core\activeform\InputWidget $class */
        $config['model'] = $this->model;
        $config['attribute'] = $this->attribute;
        $config['view'] = $this->form->getView();
        if (is_subclass_of($class, \core\activeform\InputWidget::class)) {
            foreach ($this->inputOptions as $key => $value) {
                if (!isset($config['options'][$key])) {
                    $config['options'][$key] = $value;
                }
            }
            $config['field'] = $this;
            if (!isset($config['options'])) {
                $config['options'] = [];
            }
            if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
                $this->addErrorClassIfNeeded($config['options']);
            }

            $this->addAriaAttributes($config['options']);
            $this->adjustLabelFor($config['options']);
        }

        $this->parts['{input}'] = $class::widget($config);

        return $this;
    }

    /**
     * Adjusts the `for` attribute for the label based on the input options.
     * @param array $options the input options.
     */
    protected function adjustLabelFor(array $options): void
    {
        if (!isset($options['id'])) {
            return;
        }
        $this->_inputId = $options['id'];
        if (!isset($this->labelOptions['for'])) {
            $this->labelOptions['for'] = $options['id'];
        }
    }

    /**
     * Returns the JS options for the field.
     * @return array the JS options.
     */
    protected function getClientOptions(): array
    {
        $attribute = Html::getAttributeName($this->attribute);
        if (!in_array($attribute, $this->model->activeAttributes(), true)) {
            return [];
        }

        $clientValidation = $this->isClientValidationEnabled();
        $ajaxValidation = $this->isAjaxValidationEnabled();

        $validators = [];
        if ($clientValidation) {
            foreach ($this->model->getActiveValidators($attribute) as $validator) {
                /** @var \core\validators\AttributeValidator $validator */
                $clientValidator = $validator->clientValidateAttribute();
                if ($validator->enableClientValidation && $clientValidator) {
                    $clientValidator->inputName = Html::getInputName($this->model, $this->attribute);
                    $clientValidator->inputId = $this->getInputId();
                    $validators[] = $clientValidator;
                }
            }
        }

        if (!$ajaxValidation && (!$clientValidation || empty($validators))) {
            return [];
        }

        return $validators;
    }

    /**
     * Checks if client validation enabled for the field.
     * @return bool
     */
    protected function isClientValidationEnabled(): bool
    {
        return (isset($this->enableClientValidation) && $this->enableClientValidation)
            || (!isset($this->enableClientValidation) && $this->form->enableClientValidation);
    }

    /**
     * Checks if ajax validation enabled for the field.
     * @return bool
     */
    protected function isAjaxValidationEnabled(): bool
    {
        return (isset($this->enableAjaxValidation) && $this->enableAjaxValidation)
            || (!isset($this->enableAjaxValidation) && $this->form->enableAjaxValidation);
    }

    /**
     * Adds aria attributes to the input options.
     * @param $options array input options
     */
    protected function addAriaAttributes(&$options)
    {
        if ($this->addAriaAttributes) {
            if (!isset($options['aria-required']) && $this->model->isAttributeRequired($this->attribute)) {
                $options['aria-required'] = 'true';
            }
            if (!isset($options['aria-invalid']) && $this->model->hasErrors($this->attribute)) {
                $options['aria-invalid'] = 'true';
            }
        }
    }

    /**
     * Add role attributes to the input options
     * @param $options array input options
     * @param string $role
     */
    protected function addRoleAttributes(array &$options, string $role)
    {
        if (!isset($options['role'])) {
            $options['role'] = $role;
        }
    }

    /**
     * Adds validation class to the input options if needed.
     * @param $options array input options
     */
    protected function addErrorClassIfNeeded(array &$options)
    {
        // Get proper attribute name when attribute name is tabular.
        $attributeName = Html::getAttributeName($this->attribute);

        if ($this->model->hasErrors($attributeName)) {
            Html::addCssClass($options, $this->form->errorCssClass);
        }
    }
}