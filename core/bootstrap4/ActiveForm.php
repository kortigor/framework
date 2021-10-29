<?php

declare(strict_types=1);

namespace core\bootstrap4;

use Exception;
use core\base\Model;

/**
 * A Bootstrap 4 enhanced version of [[\core\activeform\ActiveForm]].
 *
 * This class mainly adds the [[layout]] property to choose a Bootstrap 4 form layout.
 * So for example to render a horizontal form you would:
 *
 * ```php
 * use core\bootstrap4\ActiveForm;
 *
 * $form = ActiveForm::begin(['layout' => 'horizontal'])
 * ```
 *
 * This will set default values for the [[ActiveField]]
 * to render horizontal form fields. In particular the [[ActiveField::template|template]]
 * is set to `{label} {beginWrapper} {input} {error} {endWrapper} {hint}` and the
 * [[ActiveField::horizontalCssClasses|horizontalCssClasses]] are set to:
 *
 * ```php
 * [
 *     'offset' => 'offset-sm-3',
 *     'label' => 'col-sm-3',
 *     'wrapper' => 'col-sm-6',
 *     'error' => '',
 *     'hint' => 'col-sm-3',
 * ]
 * ```
 *
 * To get a different column layout in horizontal mode you can modify those options
 * through [[fieldConfig]]:
 *
 * ```php
 * $form = ActiveForm::begin([
 *     'layout' => 'horizontal',
 *     'fieldConfig' => [
 *         'template' => "{label}\n{beginWrapper}\n{input}\n{hint}\n{error}\n{endWrapper}",
 *         'horizontalCssClasses' => [
 *             'label' => 'col-sm-4',
 *             'offset' => 'offset-sm-4',
 *             'wrapper' => 'col-sm-8',
 *             'error' => '',
 *             'hint' => '',
 *         ],
 *     ],
 * ]);
 * ```
 *
 * @see ActiveField for details on the [[fieldConfig]] options
 * @see http://getbootstrap.com/css/#forms
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @author Simon Karlen <simi.albi@outlook.com>
 */
class ActiveForm extends \core\activeform\ActiveForm
{
    /**
     * Default form layout
     */
    const LAYOUT_DEFAULT = 'default';
    /**
     * Horizontal form layout
     */
    const LAYOUT_HORIZONTAL = 'horizontal';
    /**
     * Inline form layout
     */
    const LAYOUT_INLINE = 'inline';

    /**
     * @var string the default field class name when calling [[field()]] to create a new field.
     * @see fieldConfig
     */
    public string $fieldClass = ActiveField::class;
    /**
     * @var array HTML attributes for the form tag. Default is `[]`.
     */
    public array $options = [];
    /**
     * @var string the form layout. Either [[LAYOUT_DEFAULT]], [[LAYOUT_HORIZONTAL]] or [[LAYOUT_INLINE]].
     * By choosing a layout, an appropriate default field configuration is applied. This will
     * render the form fields with slightly different markup for each layout. You can
     * override these defaults through [[fieldConfig]].
     * @see \core\bootstrap4\ActiveField for details on Bootstrap 4 field configuration
     */
    public string $layout = self::LAYOUT_DEFAULT;
    /**
     * @var string the CSS class that is added to a field container when the associated attribute has validation error.
     */
    public string $errorCssClass = 'is-invalid';
    /**
     * {@inheritdoc}
     */
    public string $successCssClass = 'is-valid';
    /**
     * {@inheritdoc}
     */
    public string $errorSummaryCssClass = 'alert alert-danger';
    /**
     * {@inheritdoc}
     */
    public string $validationStateOn = self::VALIDATION_STATE_ON_INPUT;

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function init()
    {
        if (!in_array($this->layout, [self::LAYOUT_DEFAULT, self::LAYOUT_HORIZONTAL, self::LAYOUT_INLINE])) {
            throw new Exception('Invalid layout type: ' . $this->layout);
        }

        if ($this->layout === self::LAYOUT_INLINE) {
            Html::addCssClass($this->options, 'form-inline');
        }
        parent::init();
    }

    /**
     * {@inheritdoc}
     * @return \core\bootstrap4\ActiveField
     */
    public function field(Model $model, string $attribute, array $options = []): ActiveField
    {
        return parent::field($model, $attribute, $options);
    }
}