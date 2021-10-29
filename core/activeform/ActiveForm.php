<?php

declare(strict_types=1);

namespace core\activeform;

use Closure;
use core\base\Model;
use core\widgets\Widget;
use core\exception\InvalidCallException;
use common\assets\JqueryValidationAsset;
use core\helpers\ArrayHelper;
use core\helpers\Json;
use core\helpers\Html;

/**
 * ActiveForm is a widget that builds an interactive HTML form for one or multiple data models.
 */
class ActiveForm extends Widget
{
    /**
     * Add validation state class to container tag
     */
    const VALIDATION_STATE_ON_CONTAINER = 'container';
    /**
     * Add validation state class to input tag
     */
    const VALIDATION_STATE_ON_INPUT = 'input';

    /**
     * @var string the form action URL.
     * @see method for specifying the HTTP method for this form.
     */
    public string $action = '';
    /**
     * @var string the form submission method. This should be either `post` or `get`. Defaults to `post`.
     *
     * When you set this to `get` you may see the url parameters repeated on each request.
     * This is because the default value of [[action]] is set to be the current request url and each submit
     * will add new parameters instead of replacing existing ones.
     * You may set [[action]] explicitly to avoid this:
     *
     * ```php
     * $form = ActiveForm::begin([
     *     'method' => 'get',
     *     'action' => ['controller/action'],
     * ]);
     * ```
     */
    public string $method = 'post';
    /**
     * @var array the HTML attributes (name-value pairs) for the form tag.
     * @see \core\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $options = [];
    /**
     * @var string the default field class name when calling [[field()]] to create a new field.
     * @see fieldConfig
     */
    public string $fieldClass = ActiveField::class;
    /**
     * @var array|Closure the default configuration used by [[field()]] when creating a new field object.
     * This can be either a configuration array or an anonymous function returning a configuration array.
     * If the latter, the signature should be as follows:
     *
     * ```php
     * function ($model, $attribute)
     * ```
     *
     * The value of this property will be merged recursively with the `$options` parameter passed to [[field()]].
     *
     * @see fieldClass
     */
    public array|Closure $fieldConfig = [];
    /**
     * @var bool whether to perform encoding on the error summary.
     */
    public bool $encodeErrorSummary = true;
    /**
     * @var string the default CSS class for the error summary container.
     * @see errorSummary()
     */
    public string $errorSummaryCssClass = 'error-summary';
    /**
     * @var string the CSS class that is added to a field container when the associated attribute is required.
     */
    public string $requiredCssClass = 'required';
    /**
     * @var string the CSS class that is added to a field container when the associated attribute has validation error.
     */
    public string $errorCssClass = 'has-error';
    /**
     * @var string the CSS class that is added to a field container when the associated attribute is successfully validated.
     */
    public string $successCssClass = 'has-success';
    /**
     * @var string the CSS class that is added to a field container when the associated attribute is being validated.
     */
    public string $validatingCssClass = 'validating';
    /**
     * @var string where to render validation state class
     * Could be either "container" or "input".
     * Default is "container".
     */
    public string $validationStateOn = self::VALIDATION_STATE_ON_CONTAINER;
    /**
     * @var bool whether to hook up `activeForm` JavaScript plugin.
     * This property must be set `true` if you want to support client validation and/or AJAX validation.
     * When this is `false`, the form will not generate any JavaScript.
     * @see registerClientScript
     */
    public bool $enableClientScript = true;
    /**
     * @var bool whether to enable client-side data validation.
     * If `ActiveField::enableClientValidation` is set, its value will take precedence for that input field.
     */
    public bool $enableClientValidation = true;
    /**
     * @var bool client validation options (jQuery validation plugin).
     */
    public array $clientValidationOptions = [];
    /**
     * @var bool whether to enable AJAX-based data validation.
     * If `ActiveField::enableAjaxValidation` is set, its value will take precedence for that input field.
     */
    public bool $enableAjaxValidation = false;
    /**
     * @var string the URL for performing AJAX-based validation.
     * If this property is not set, it will take the value of the form's action attribute.
     */
    public string $validationUrl;
    /**
     * @var bool whether to submit form by ajax request
     */
    public bool $submitByAjax = false;
    /**
     * @var array ajax submitter options
     * @see jQuery.ajax options
     */
    public array $ajaxSubmitOptions = [];
    /**
     * @var string spinner to add near submit button in ajax submitter.
     */
    public string $ajaxSubmitSpinner = '<div class="spinner-border spinner-border-sm text-primary ml-2"></div>';
    /**
     * @var string JavaScript function name to execute before submit.
     * MUST return Promise or JQuery.Deferred object, and require no arguments, such as:
     * ```
     * function before() {
     * 			let dfd = $.Deferred();
     *          ...
     * 			dfd.reject('error');
     *          ...
     * 			dfd.resolve('complete');
     *          ...
     * 			return dfd.promise();
     * }
     * ```
     * Form submit starts only after this function resolved successfully.
     * @see wrapToBeforePromise()
     */
    public string $beforeSubmitPromise;
    /**
     * @var bool whether to scroll to the first error after validation.
     */
    public bool $scrollToError = true;
    /**
     * @var int offset in pixels that should be added when scrolling to the first error.
     */
    public int $scrollToErrorOffset = 30;
    /**
     * @var array the client validation options for individual attributes. Each element of the array
     * represents the validation options for a particular attribute.
     * @internal
     */
    public array $attributes = [];

    /**
     * @var ActiveField[] the ActiveField objects that are currently active
     */
    private array $_fields = [];

    /**
     * Initializes the widget.
     * This renders the form open tag.
     */
    public function init()
    {
        parent::init();
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * Runs the widget.
     * This registers the necessary JavaScript code and renders the form open and close tags.
     * @throws InvalidCallException if `beginField()` and `endField()` calls are not matching.
     */
    public function run()
    {
        if (!empty($this->_fields)) {
            throw new InvalidCallException('Each beginField() should have a matching endField() call.');
        }

        $content = ob_get_clean();
        $html = Html::beginForm($this->action, $this->method, $this->options);
        $html .= $content;

        if ($this->enableClientScript) {
            $this->registerClientScript();
        }

        $html .= Html::endForm();
        return $html;
    }

    /**
     * This registers the necessary JavaScript code.
     */
    public function registerClientScript()
    {
        $view = $this->getView();
        ActiveFormAsset::register($view);

        $id = $this->options['id'];
        $options = $this->getClientOptions();
        $view->registerJs('activeForm = new ActiveForm(' . Json::htmlEncode($options) . ');');

        if ($this->scrollToError && !$this->submitByAjax) {
            $view->registerJs('activeForm.scrollToError();');
        }

        // Client validation enabled. Validate by ajax request
        if ($this->enableAjaxValidation) {
            $this->registerAjaxValidationCode($id);
            return;
        }

        // Client validation enabled and have configured client validation rules
        if ($this->enableClientValidation && $this->attributes) {
            $this->registerCliendValidationCode($id);
            return;
        }

        // Only submit by ajax, without any validation
        if ($this->submitByAjax) {
            $this->registerAjaxSubmitCode($id);
        }
    }

    /**
     * Return ajax submit options
     * 
     * @return array
     */
    protected function getAjaxSubmitOptions(): array
    {
        $defaultAjaxSubmitOptions = [
            'contentType' => false,
            'processData' => false,
            'cache' => false
        ];
        return array_merge($defaultAjaxSubmitOptions, $this->ajaxSubmitOptions);
    }

    /**
     * Register JS code for ajax validation.
     * 
     * @param string $id Form id
     * 
     * @return void
     */
    protected function registerAjaxValidationCode(string $id): void
    {
        $this->validationUrl ??= $this->action;
        $ajaxValidationOptions = [
            'url' => new JsExpression("'" . $this->validationUrl . "'"),
            'type' => 'POST',
            'contentType' => false,
            'processData' => false,
            'cache' => false,
        ];

        if ($this->submitByAjax) {
            $ajaxValidationOptions['submitHandler'] = new JsExpression(
                "function(form) { activeForm.ajaxSubmit(form, " . Json::htmlEncode($this->getAjaxSubmitOptions()) . "); }"
            );
        } else {
            $ajaxValidationOptions['submitHandler'] = new JsExpression("function(form) { form.submit(); }");
        }

        $code = "activeForm.ajaxValidateSubmit($(this), " . Json::htmlEncode($ajaxValidationOptions) . ");";
        if (isset($this->beforeSubmitPromise)) {
            $code = $this->wrapToBeforePromise($code);
        }

        $codeAjaxValidation = "jQuery('html').on('submit', '#" . $id . "', function(e) {
                e.preventDefault();
                " . $code . "
            });";

        $this->getView()->registerJs($codeAjaxValidation);
    }

    /**
     * Register JS code for client validation.
     * Setup validation rules and messages.
     * 
     * @param string $id Form id
     * 
     * @return void
     */
    protected function registerCliendValidationCode(string $id): void
    {
        $clientValidationOptions = [];
        foreach ($this->attributes as $attribute) {
            /** @var \core\validators\ClientAttributeValidator $validator */
            foreach ($attribute as $validator) {
                $clientValidationOptions['rules'][$validator->inputName][$validator->getMethod()] = $validator->getArguments() ?: true;
                if ($validator->message) {
                    $clientValidationOptions['messages'][$validator->inputName][$validator->getMethod()] = $validator->message;
                }
            }
        }

        if ($this->submitByAjax) {
            $code = "activeForm.ajaxSubmit(form, " . Json::htmlEncode($this->getAjaxSubmitOptions()) . ");";
            if (isset($this->beforeSubmitPromise)) {
                $code = $this->wrapToBeforePromise($code);
            }

            $clientValidationOptions['submitHandler'] = new JsExpression(
                "function(form) { " . $code . " }"
            );
        } else {
            // Standard handler
            $code = "form.submit();";
            if (isset($this->beforeSubmitPromise)) {
                $code = $this->wrapToBeforePromise($code);
            }

            $clientValidationOptions['submitHandler'] = new JsExpression("function(form) { " . $code . " }");
        }

        $clientValidationOptions = array_merge($clientValidationOptions, $this->clientValidationOptions);
        $codeClientValidation = "activeForm.validator = jQuery('#" . $id . "').validate(" . Json::htmlEncode($clientValidationOptions) . ");";
        $view = $this->getView();
        JqueryValidationAsset::register($view);
        $view->registerJs($codeClientValidation);
    }

    /**
     * Register JS code for ajax submit.
     * 
     * @param string $id Form id
     * 
     * @return void
     */
    protected function registerAjaxSubmitCode(string $id): void
    {
        $code = "activeForm.ajaxSubmit($(this), " . Json::htmlEncode($this->getAjaxSubmitOptions()) . ");";
        if (isset($this->beforeSubmitPromise)) {
            $code = $this->wrapToBeforePromise($code);
        }

        $codeSubmit = "jQuery('html').on('submit', '#" . $id . "', function(e) {
            e.preventDefault();
            " . $code . "
        });";

        $this->getView()->registerJs($codeSubmit);
    }

    /**
     * Wrap JS code to 'beforeSubmitPromise' function
     * 
     * @param string $code
     * 
     * @return string
     * @see $beforeSubmitPromise
     */
    protected function wrapToBeforePromise(string $code): string
    {
        return $this->beforeSubmitPromise . "().done(() => {\n" . $code . "\n});";
    }

    /**
     * Returns the options for the form JS widget.
     * 
     * @return array the options.
     */
    protected function getClientOptions(): array
    {
        return [
            'encodeErrorSummary' => $this->encodeErrorSummary,
            'errorSummary' => '.' . implode('.', preg_split('/\s+/', $this->errorSummaryCssClass, -1, PREG_SPLIT_NO_EMPTY)),
            'scrollToError' => $this->scrollToError,
            'scrollToErrorOffset' => $this->scrollToErrorOffset,
            'ajaxSubmitSpinner' => new JsExpression("'" . $this->ajaxSubmitSpinner . "'"),
        ];
    }

    /**
     * Generates a summary of the validation errors.
     * If there is no validation error, an empty error summary markup will still be generated, but it will be hidden.
     * @param Model|Model[] $models the model(s) associated with this form.
     * @param array $options the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - `header`: string, the header HTML for the error summary. If not set, a default prompt string will be used.
     * - `footer`: string, the footer HTML for the error summary.
     *
     * The rest of the options will be rendered as the attributes of the container tag. The values will
     * be HTML-encoded using [[\core\helpers\Html::encode()]]. If a value is `null`, the corresponding attribute will not be rendered.
     * @return string the generated error summary.
     * @see errorSummaryCssClass
     */
    public function errorSummary($models, array $options = []): string
    {
        Html::addCssClass($options, $this->errorSummaryCssClass);
        $options['encode'] = $this->encodeErrorSummary;
        return Html::errorSummary($models, $options);
    }

    /**
     * Generates a form field.
     * A form field is associated with a model and an attribute. It contains a label, an input and an error message
     * and use them to interact with end users to collect their inputs for the attribute.
     * @param Model $model the data model.
     * @param string $attribute the attribute name or expression. See [[Html::getAttributeName()]] for the format
     * about attribute expression.
     * @param array $options the additional configurations for the field object. These are properties of [[ActiveField]]
     * or a subclass, depending on the value of [[fieldClass]].
     * @return ActiveField the created ActiveField object.
     * @see fieldConfig
     */
    public function field(Model $model, string $attribute, array $options = []): ActiveField
    {
        $config = $this->fieldConfig;
        if ($config instanceof Closure) {
            $config = call_user_func($config, $model, $attribute);
        }

        $class = $config['class'] ?? $this->fieldClass;

        return new $class(ArrayHelper::merge($config, $options, [
            'model' => $model,
            'attribute' => $attribute,
            'form' => $this,
        ]));
    }

    /**
     * Begins a form field.
     * This method will create a new form field and returns its opening tag.
     * You should call [[endField()]] afterwards.
     * @param Model $model the data model.
     * @param string $attribute the attribute name or expression. See [[Html::getAttributeName()]] for the format
     * about attribute expression.
     * @param array $options the additional configurations for the field object.
     * @return string the opening tag.
     * @see endField()
     * @see field()
     */
    public function beginField(Model $model, string $attribute, array $options = []): string
    {
        $field = $this->field($model, $attribute, $options);
        $this->_fields[] = $field;
        return $field->begin();
    }

    /**
     * Ends a form field.
     * This method will return the closing tag of an active form field started by [[beginField()]].
     * @return string the closing tag of the form field.
     * @throws InvalidCallException if this method is called without a prior [[beginField()]] call.
     */
    public function endField(): string
    {
        $field = array_pop($this->_fields);
        if ($field instanceof ActiveField) {
            return $field->end();
        }

        throw new InvalidCallException('Mismatching endField() call.');
    }

    /**
     * Validates one or several models and returns an error message array indexed by the attribute IDs.
     * This is a helper method that simplifies the way of writing AJAX validation code.
     *
     * For example, you may use the following code in a controller action to respond
     * to an AJAX validation request:
     *
     * ```php
     * $model = new Post;
     * $model->fill($this->request->post());
     * if ($this->request->isAjax()) {
     *     $this->response->setFormat(ContentType::FORMAT_JSON);
     *     return ActiveForm::validate($model);
     * }
     * // ... respond to non-AJAX request ...
     * ```
     *
     * To validate multiple models, simply pass each model as a parameter to this method, like
     * the following:
     *
     * ```php
     * ActiveForm::validate($model1, $model2, ...);
     * ```
     *
     * @param Model $model the model to be validated.
     * @param mixed $attributes list of attributes that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable
     * validation rules should be validated.
     *
     * When this method is used to validate multiple models, this parameter will be interpreted
     * as a model.
     *
     * @return array the error message array indexed by the attribute IDs.
     */
    public static function validate(Model $model, $attributes = null): array
    {
        $result = [];
        if ($attributes instanceof Model) {
            // validating multiple models
            $models = func_get_args();
            $attributes = null;
        } else {
            $models = [$model];
        }
        /** @var Model $model */
        foreach ($models as $model) {
            $model->validate($attributes);
            foreach ($model->getErrors() as $attribute => $errors) {
                $result[Html::getInputId($model, $attribute)] = $errors;
            }
        }

        return $result;
    }

    /**
     * Validates an array of model instances and returns an error message array indexed by the attribute IDs.
     * This is a helper method that simplifies the way of writing AJAX validation code for tabular input.
     *
     * For example, you may use the following code in a controller action to respond
     * to an AJAX validation request:
     *
     * ```php
     * // ... load $models ...
     * if ($this->request->isAjax()) {
     *     $this->response->setFormat(ContentType::FORMAT_JSON);
     *     return ActiveForm::validateMultiple($models);
     * }
     * // ... respond to non-AJAX request ...
     * ```
     *
     * @param array $models an array of models to be validated.
     * @param mixed $attributes list of attributes that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable
     * validation rules should be validated.
     * @return array the error message array indexed by the attribute IDs.
     */
    public static function validateMultiple(array $models, $attributes = null): array
    {
        $result = [];
        /** @var Model $model */
        foreach ($models as $i => $model) {
            $model->validate($attributes);
            foreach ($model->getErrors() as $attribute => $errors) {
                $result[Html::getInputId($model, "[$i]" . $attribute)] = $errors;
            }
        }

        return $result;
    }
}