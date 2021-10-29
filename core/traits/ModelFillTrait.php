<?php

declare(strict_types=1);

namespace core\traits;

use ReflectionClass;
use ReflectionProperty;

trait ModelFillTrait
{
    /**
     * The attributes that are mass assignable.
     * 
     * Importantly, you should use either $fillable or $guarded - not both.
     * 
     * If you set same attribute fillable and guarded, the attribute will be fillable.
     *
     * @var array
     * @see https://laravel.com/docs/7.x/eloquent#mass-assignment
     */
    protected array $fillable = [];

    /**
     * The attributes that are no mass assignable.
     * 
     * Importantly, you should use either $fillable or $guarded - not both.
     * 
     * If you set same attribute fillable and guarded, the attribute will be fillable.
     * 
     * If sets to '['*']' it means all attributes is guarded.
     *
     * @var array
     * @see https://laravel.com/docs/7.x/eloquent#mass-assignment
     */
    protected array $guarded = [];


    /**
     * Populates the model with input data.
     *
     * This method provides a convenient shortcut for:
     *
     * ```php
     * if (isset($_POST['FormName'])) {
     *     $model->attributes = $_POST['FormName'];
     *     if ($model->save()) {
     *         // handle success
     *     }
     * }
     * ```
     *
     * which, with `fill()` can be written as:
     *
     * ```php
     * if ($model->fill($_POST) && $model->save()) {
     *     // handle success
     * }
     * ```
     *
     * `fill()` gets the `'FormName'` from the model's `formName()` method (which you may override), unless the
     * `$formName` parameter is given. If the form name is empty, `fill()` populates the model with the whole of `$data`,
     * instead of `$data['FormName']`.
     *
     * Note, that the data being populated is subject to the safety check by `setAttributes()`.
     *
     * @param array|null $data the data array to load, typically `$_POST` or `$_GET`.
     * @param string $formName the form name to use to load the data into the model.
     * If not set, `formName()` is used.
     * @return bool whether `fill()` found the expected form in `$data`.
     */
    public function fill(?array $data, string $formName = null): bool
    {
        $scope = $formName ?? $this->formName();
        if ($scope === '' && !empty($data)) {
            $this->setAttributes($data);
            return true;
        } elseif (isset($data[$scope])) {
            $this->setAttributes($data[$scope]);
            return true;
        }

        return false;
    }

    /**
     * Populates a set of models with the data from end user.
     * This method is mainly used to collect tabular data input.
     * The data to be loaded for each model is `$data[formName][index]`, where `formName`
     * refers to the value of `formName()`, and `index` the index of the model in the `$models` array.
     * If `formName()` is empty, `$data[index]` will be used to populate each model.
     * The data being populated to each model is subject to the safety check by `setAttributes()`.
     * @param \core\base\Model[] $models the models to be populated. Note that all models should have the same class.
     * @param array|null $data the data array. This is usually `$_POST` or `$_GET`, but can also be any valid array
     * supplied by end user.
     * @param string $formName the form name to be used for loading the data into the models.
     * If not set, it will use the `formName()` value of the first model in `$models`.
     * @return bool whether at least one of the models is successfully populated.
     */
    public static function fillMultiple(array $models, ?array $data, string $formName = null): bool
    {
        if ($formName === null) {
            /** @var \core\base\Model|false $first  */
            $first = reset($models);
            if ($first === false) {
                return false;
            }
            $formName = $first->formName();
        }

        $success = false;
        foreach ($models as $i => $model) {
            if ($formName === '') {
                if (!empty($data[$i]) && $model->fill($data[$i], '')) {
                    $success = true;
                }
            } elseif (!empty($data[$formName][$i]) && $model->fill($data[$formName][$i], '')) {
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Sets the attribute values in a massive way.
     * @param array $values attribute values (name => value) to be assigned to the model.
     * @param bool $safeOnly whether the assignments should only be done to the safe attributes.
     * A safe attribute is one that is associated with a validation rule in the current `scenario`.
     * 
     * @return void
     * @see safeAttributes()
     * @see attributes()
     */
    public function setAttributes(array $values, $safeOnly = true): void
    {
        $attributes = array_flip($safeOnly ? $this->safeAttributes() : $this->attributes());
        foreach ($values as $name => $value) {
            if (isset($attributes[$name])) {
                $this->$name = $value;
            }
        }
    }

    /**
     * Returns the list of attribute names.
     * By default, this method returns all public non-static properties of the class.
     * You may override this method to change the default behavior.
     * @return string[] list of attribute names.
     */
    public function attributes(): array
    {
        $class = new ReflectionClass($this);
        $names = [];
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $names[] = $property->getName();
            }
        }

        return $names;
    }

    /**
     * Returns the attribute names that are safe to be massively assigned.
     * @return string[] safe attribute names
     */
    public function safeAttributes(): array
    {
        if (empty($this->fillable) && empty($this->guarded)) {
            return $this->attributes();
        } elseif (!empty($this->fillable)) {
            return $this->fillable;
        } elseif (!empty($this->guarded)) {
            if ($this->guarded === ['*']) {
                return [];
            }
            return array_diff($this->attributes(), $this->guarded);
        }
    }

    /**
     * Returns a value indicating whether the attribute is safe for massive assignments.
     * @param string $attribute attribute name
     * @return bool whether the attribute is safe for massive assignments
     * @see safeAttributes()
     */
    public function isAttributeSafe(string $attribute): bool
    {
        return in_array($attribute, $this->safeAttributes(), true);
    }

    /**
     * Returns the form name that this model class should use.
     *
     * The form name is mainly used to determine how to name
     * the input fields for the attributes in a model. If the form name is "A" and an attribute
     * name is "b", then the corresponding input name would be "A[b]". If the form name is
     * an empty string, then the input name would be "b".
     *
     * The purpose of the above naming schema is that for forms which contain multiple different models,
     * the attributes of each model are grouped in sub-arrays of the POST-data and it is easier to
     * differentiate between them.
     *
     * By default, this method returns the model class name (without the namespace part)
     * as the form name. You may override it when the model is used in different forms.
     *
     * @return string the form name of this model class.
     * @see fill()
     */
    public function formName(): string
    {
        return get_class_short($this);
    }

    /**
     * Same as `formName()`, but used as static call
     * 
     * @return string
     */
    public static function getFormName(): string
    {
        return get_class_short(static::class);
    }
}