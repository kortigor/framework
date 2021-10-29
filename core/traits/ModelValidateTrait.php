<?php

declare(strict_types=1);

namespace core\traits;

use InvalidArgumentException;
use core\interfaces\ModelValidableInterface;
use core\orm\ActiveRecord;
use core\helpers\Inflector;
use core\validators\Assert;
use core\validators\ValidatorCollection;
use core\validators\NormalizatorCollection;

trait ModelValidateTrait
{
    /**
     * @var ValidatorCollection compiled validators collection.
     */
    private ValidatorCollection $validators;

    /**
     * @var NormalizatorCollection compiled normalizators collection.
     */
    private NormalizatorCollection $normalizators;

    /**
     * @var array errors list.
     */
    private array $errors = [];

    /**
     * @var string current scenario
     */
    private string $scenario = ModelValidableInterface::SCENARIO_DEFAULT;

    /**
     * @var array backup of default `fillable` attribute
     */
    private array $default_fillable;

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules can utilize `core\validators\Assert` class methods
     * or self methods to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * Each rule is an array with the following structure:
     *
     * ```php
     * // validator_name is optional
     * 'validator_name' => [
     *      ['attribute1', 'attribute2'], // required
     *      'validator_name', // required
     * // from here and below options are optional
     * // arguments passed to the validation function
     * // if argumet specified like '{attributeName}' it means attribute of model `$this->attributeName`
     *      'argument1', 'argument2',
     *      'message' => 'Error message',
     *      // ...other options...
     * ],
     * //...other rules...
     * ```
     * Required: attributes, validator_name
     * 
     * @return array
     * @see \core\validators\Assert
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Returns normalizators for attributes
     * 
     * Normalizators used to initial process attributes values, i.e. `trim()`
     * 
     * Each normalizator is an array with the following structure:
     * 
     * ```
     * [
     *  ['attribute1', 'attribute2'...], // required
     *  'function_name' // required
     * ]
     * 
     * ```
     * 
     * @return array
     */
    public function normalizators(): array
    {
        return [];
    }

    /**
     * Returns a list of scenarios and the corresponding active attributes.
     *
     * An active attribute is one that is subject to validation in the current scenario.
     * The returned array should be in the following format:
     *
     * ```php
     * [
     *     'scenario1' => ['attribute11', 'attribute12', ...],
     *     'scenario2' => ['attribute21', 'attribute22', ...],
     *     ...
     * ]
     * ```
     *
     * By default, an active attribute is considered safe and can be massively assigned.
     * If an attribute should NOT be massively assigned (thus considered unsafe),
     * please prefix the attribute with an exclamation character (e.g. `'!rank'`).
     *
     * The default implementation of this method will return all scenarios found in the `rules()`
     * declaration. A special scenario named `SCENARIO_DEFAULT` will contain all attributes
     * found in the `rules()`. Each scenario will be associated with the attributes that
     * are being validated by the validation rules that apply to the scenario.
     *
     * @return array a list of scenarios and the corresponding active attributes.
     */
    public function scenarios(): array
    {
        $scenarios = [ModelValidableInterface::SCENARIO_DEFAULT => []];
        foreach ($this->getValidators() as $validator) {
            foreach ($validator->on as $scenario) {
                $scenarios[$scenario] = [];
            }
            foreach ($validator->except as $scenario) {
                $scenarios[$scenario] = [];
            }
        }
        $names = array_keys($scenarios);

        foreach ($this->getValidators() as $validator) {
            if (empty($validator->on) && empty($validator->except)) {
                foreach ($names as $name) {
                    $scenarios[$name][$validator->attribute] = true;
                }
            } elseif (empty($validator->on)) {
                foreach ($names as $name) {
                    if (!in_array($name, $validator->except, true)) {
                        $scenarios[$name][$validator->attribute] = true;
                    }
                }
            } else {
                foreach ($validator->on as $name) {
                    $scenarios[$name][$validator->attribute] = true;
                }
            }
        }

        foreach ($scenarios as $scenario => $attributes) {
            if (!empty($attributes)) {
                $scenarios[$scenario] = array_keys($attributes);
            }
        }

        return $scenarios;
    }

    /**
     * Returns the scenario that this model is used in.
     *
     * Scenario affects how validation is performed and which attributes can
     * be massively assigned.
     *
     * @return string the scenario that this model is in. Defaults to `SCENARIO_DEFAULT`.
     */
    public function getScenario(): string
    {
        return $this->scenario;
    }

    /**
     * Sets the scenario for the model.
     * Note that this method does not check if the scenario exists or not.
     * The method `validate()` will perform this check.
     * @param string $value the scenario that this model is in.
     */
    public function setScenario(string $value): self
    {
        $default = ModelValidableInterface::SCENARIO_DEFAULT;
        if ($value === $default && $this->getScenario() === $default) {
            return $this;
        }

        $scenarios = $this->scenarios();
        if (isset($scenarios[$value])) {
            if ($this->getScenario() === $default) {
                $this->default_fillable = $this->fillable;
            }

            if ($value === $default) {
                $this->fillable = $this->default_fillable;
            } else {
                $this->fillable = $scenarios[$value];
            }
        }

        $this->scenario = $value;

        return $this;
    }

    /**
     * Normalize model attributes.
     * 
     * @return void
     */
    public function normalize(): void
    {
        $attributes = $this->activeAttributes();
        /** @var \core\validators\AttributeNormalizator $normalizator */
        foreach ($this->getNormalizators() as $normalizator) {
            $attribute = $normalizator->attribute;
            if (in_array($attribute, $attributes)) {
                $this->$attribute = $normalizator->run($this->$attribute);
            }
        }
    }

    /**
     * Validate model.
     * 
     * @param string[]|string $attributeNames attribute name or list of attribute names that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable validation rules should be validated.
     * 
     * @param boolean $clearErrors clear errors before validation or no
     * 
     * @return bool true if no errors during validation
     */
    public function validate(string|array $attributeNames = null, bool $clearErrors = true): bool
    {
        if ($clearErrors) {
            $this->clearErrors();
        }

        if (!$this->beforeValidate()) {
            return false;
        }

        $this->normalize();

        $scenarios = $this->scenarios();
        $scenario = $this->getScenario();
        if (!isset($scenarios[$scenario])) {
            throw new InvalidArgumentException(sprintf('Unknown scenario: "%s"', $scenario));
        }

        if ($attributeNames === null) {
            $attributeNames = $this->activeAttributes();
        }
        $attributeNames = (array) $attributeNames;

        foreach ($this->getActiveValidators() as $validator) {
            $attribute = $validator->attribute;
            if (!in_array($attribute, $attributeNames)) {
                continue;
            }

            // ActiveRecord model can have attribute casting or mutator.
            // To validation we need original attribute value, from $attributes property array.
            $value = $this instanceof ActiveRecord ? $this->getAttributeFromArray($attribute) : $this->$attribute;
            if (!$validator->run($value)) {
                $this->addError($attribute, $validator->message, $validator->getName());
            }
        }

        $this->afterValidate();

        return !$this->hasErrors();
    }

    /**
     * This method is invoked before validation starts.
     * You may override this method to do preliminary checks before validation.
     * @return bool whether the validation should be executed. Defaults to true.
     * If false is returned, the validation will stop and the model is considered invalid.
     */
    public function beforeValidate(): bool
    {
        return true;
    }

    /**
     * This method is invoked after validation ends.
     * You may override this method to do postprocessing after validation.
     */
    public function afterValidate()
    {
    }

    /**
     * Validates multiple models.
     * This method will validate every model. The models being validated may
     * be of the same or different types.
     * @param ModelValidableInterface[] $models the models to be validated
     * @param string[]|string $attributeNames list of attribute names that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable
     * validation rules should be validated.
     * @return bool whether all models are valid. False will be returned if one
     * or multiple models have validation error.
     */
    public static function validateMultiple(array $models, string|array $attributeNames = null, bool $clearErrors = true): bool
    {
        $valid = true;
        /** @var ModelValidableInterface $model */
        foreach ($models as $model) {
            Assert::implementsInterface($model, ModelValidableInterface::class);
            $valid = $model->validate($attributeNames, $clearErrors) && $valid;
        }

        return $valid;
    }

    /**
     * Adds a validation rule to this model.
     * You can also directly manipulate validators to add or remove validation rules.
     * This method provides a shortcut.
     * @param string $attribute the attribute to be validated by the rule
     * @param array $validator the validator for the rule.
     * @param array $options the options to be applied to the validator
     * @return $this the model itself
     */
    public function addRule(string $attribute, array $validator, array $options = []): self
    {
        $this->getValidators()->add($attribute, $validator, $options);
        return $this;
    }

    /**
     * Returns all the normalizators declared in `normalizators()`.
     *
     * Because this method returns an NormalizatorCollection object, you may
     * manipulate it by inserting or removing normalizators.
     * For example,
     *
     * ```php
     * $model->normalizators[] = $newNormalizator;
     * same as
     * $model->getNormalizators()[] = $newNormalizator;
     * ```
     * @return NormalizatorCollection all the normalizators declared in the model.
     */
    public function getNormalizators(): NormalizatorCollection
    {
        if (!isset($this->normalizators)) {
            $this->normalizators = NormalizatorCollection::createFromArray($this, $this->normalizators());
        }

        return $this->normalizators;
    }

    /**
     * Adds normalizator to this model.
     * 
     * @param string $attribute $attribute Model attribute name to normalize
     * @param string $normalizator Normalization method.
     * 
     * @return self
     * @see NormalizatorCollection::add()
     */
    public function addNormalizator(string $attribute, string $normalizator): self
    {
        $this->getNormalizators()->add($attribute, $normalizator);
        return $this;
    }

    /**
     * @param string $validator validator name
     * @param string $attribute model attribute name
     * @param mixed string error message
     * 
     * @return void
     */
    public function addError(string $attribute, string $message, string $validator = ''): void
    {
        $this->errors[$attribute][] = [
            'message' => $message,
            'validator' => $validator,
        ];
    }

    /**
     * Returns the errors for all attributes or a single attribute.
     * @param string $attribute attribute name. Use null to retrieve errors for all attributes.
     * @property array An array of errors for all attributes. Empty array is returned if no error.
     * The result is a two-dimensional array. See `getErrors()` for detailed description.
     * @return array errors for all attributes or the specified attribute. Empty array is returned if no error.
     * Note that when returning errors for all attributes, the result is a two-dimensional array, like the following:
     *
     * ```php
     * [
     *     'username' => [
     *         'Username is required.',
     *         'Username must contain only word characters.',
     *     ],
     *     'email' => [
     *         'Email address is invalid.',
     *     ]
     * ]
     * ```
     *
     * @see getFirstErrors()
     * @see getFirstError()
     */
    public function getErrors(string $attribute = null): array
    {
        if ($attribute === null) {
            if ($this->errors === null) {
                return [];
            }
            return $this->getErrorMessagesOnly($this->errors);
        } elseif (isset($this->errors[$attribute])) {
            return $this->getErrorMessagesOnly($this->errors[$attribute]);
        } else {
            return [];
        }
    }


    /**
     * Get only messages from errors array
     * 
     * @param array $errors
     * 
     * @return string[]
     */
    public function getErrorMessagesOnly(array $errors): array
    {
        $out = [];
        foreach ($errors as $name => $err) {
            if (is_array($err)) {
                foreach ($err as $er) {
                    $out[$name][] = $er['message'] ?? '';
                }
            } else {
                $out[$name][] = $err['message'] ?? '';
            }
        }
        return $out;
    }

    /**
     * Return full errors description with validator names
     * 
     * @param string $attribute
     * 
     * @return array
     */
    public function getFullErrors(string $attribute = null): array
    {
        if ($attribute === null) {
            return $this->errors === null ? [] : $this->errors;
        }

        return isset($this->errors[$attribute]) ? $this->errors[$attribute] : [];
    }

    /**
     * Returns the first error of every attribute in the model.
     * @return array the first errors. The array keys are the attribute names, and the array
     * values are the corresponding error messages. An empty array will be returned if there is no error.
     * @see getErrors()
     * @see getFirstError()
     */
    public function getFirstErrors(): array
    {
        if (empty($this->errors)) {
            return [];
        }

        $errors = [];
        foreach ($this->errors as $name => $es) {
            if (!empty($es)) {
                $errors[$name] = reset($es)['message'];
            }
        }

        return $errors;
    }

    /**
     * Returns the first error of the specified attribute.
     * @param string $attribute attribute name.
     * @return string the error message. Null is returned if no error.
     * @see getErrors()
     * @see getFirstErrors()
     */
    public function getFirstError(string $attribute): ?string
    {
        // return isset($this->errors[$attribute]) ? reset($this->errors[$attribute]) : null;
        return isset($this->errors[$attribute]) ? reset($this->errors[$attribute])['message'] : null;
    }

    /**
     * Returns the errors for all attributes as a one-dimensional array.
     * @param bool $showAllErrors boolean, if set to true every error message for each attribute will be shown otherwise
     * only the first error message for each attribute will be shown.
     * @return array errors for all attributes as a one-dimensional array. Empty array is returned if no error.
     * @see getErrors()
     * @see getFirstErrors()
     */
    public function getErrorSummary(bool $showAllErrors): array
    {
        $lines = [];
        $errors = $showAllErrors ? $this->getErrors() : $this->getFirstErrors();
        foreach ($errors as $es) {
            $lines = array_merge((array) $es, $lines);
        }
        return $lines;
    }

    /**
     * Returns all the validators declared in `rules()`.
     *
     * This method differs from `getActiveValidators()` in that the latter
     * only returns the validators applicable to the current [[scenario]].
     *
     * Because this method returns an ValidatorCollection object, you may
     * manipulate it by inserting or removing validators (useful in model behaviors).
     * For example,
     *
     * ```php
     * $model->validators[] = $newValidator;
     * same as
     * $model->getValidators()[] = $newValidator;
     * ```
     *
     * @return ValidatorCollection all the validators declared in the model.
     */
    public function getValidators(): ValidatorCollection
    {
        if (!isset($this->validators)) {
            $this->validators = ValidatorCollection::createFromArray($this, $this->rules());
        }

        return $this->validators;
    }

    /**
     * Removes errors for all attributes or a single attribute.
     * @param string $attribute attribute name. Use null to remove errors for all attributes.
     * @return void
     */
    public function clearErrors(string $attribute = null): void
    {
        if ($attribute === null) {
            $this->errors = [];
        } else {
            unset($this->errors[$attribute]);
        }
    }

    /**
     * Returns a value indicating whether there is any validation error.
     * @param string|null $attribute attribute name. Use null to check all attributes.
     * @return bool whether there is any error.
     */
    public function hasErrors(string $attribute = null): bool
    {
        return $attribute === null ? !empty($this->errors) : isset($this->errors[$attribute]);
    }

    /**
     * Returns the attribute names that are subject to validation in the current scenario.
     * @return string[] attribute names
     */
    public function activeAttributes(): array
    {
        $scenario = $this->getScenario();
        $scenarios = $this->scenarios();
        if (!isset($scenarios[$scenario])) {
            return [];
        }
        $attributes = array_keys(array_flip($scenarios[$scenario]));
        foreach ($attributes as $i => $attribute) {
            $attributes[$i] = $attribute;
        }

        return $attributes;
    }

    /**
     * Returns a value indicating whether the attribute is active in the current scenario.
     * @param string $attribute attribute name
     * @return bool whether the attribute is active in the current scenario
     * @see activeAttributes()
     */
    public function isAttributeActive(string $attribute): bool
    {
        return in_array($attribute, $this->activeAttributes(), true);
    }

    /**
     * Returns the applicable validators.
     * @param string $attribute the name of the attribute whose applicable validators should be returned.
     * If this is null, the validators for ALL attributes in the model will be returned.
     * @return \core\validators\AttributeValidator[] the applicable validators.
     */
    public function getActiveValidators(string $attribute = null): array
    {
        $activeAttributes = $this->activeAttributes();
        if ($attribute !== null && !in_array($attribute, $activeAttributes, true)) {
            return [];
        }

        $scenario = $this->getScenario();
        $validators = [];
        /** @var \core\validators\AttributeValidator $validator */
        foreach ($this->getValidators() as $validator) {
            if ($attribute === null) {
                $attributeValid = true;
            } else {
                $attributeValid = $attribute == $validator->attribute;
            }
            if ($attributeValid && $validator->isActive($scenario)) {
                $validators[] = $validator;
            }
        }

        return $validators;
    }

    /**
     * Returns a value indicating whether the attribute is required.
     * This is determined by checking if the attribute is associated with a
     * 'required' validation rule.
     *
     * @param string $attribute attribute name
     * @return bool whether the attribute is required
     */
    public function isAttributeRequired(string $attribute): bool
    {
        foreach ($this->getActiveValidators($attribute) as $validator) {
            if ($validator->getName() === 'required') {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the text label for the specified attribute.
     * @param string $attribute the attribute name
     * @return string the attribute label
     * @see generateAttributeLabel()
     * @see attributeLabels()
     */
    public function getAttributeLabel(string $attribute): string
    {
        $labels = $this->attributeLabels();
        return $labels[$attribute] ?? $this->generateAttributeLabel($attribute);
    }

    /**
     * Returns the text hint for the specified attribute.
     * @param string $attribute the attribute name
     * @return string the attribute hint
     * @see attributeHints()
     */
    public function getAttributeHint(string $attribute): string
    {
        $hints = $this->attributeHints();
        return $hints[$attribute] ?? '';
    }

    /**
     * Returns the attribute labels.
     *
     * Attribute labels are mainly used for display purpose. For example, given an attribute
     * `firstName`, we can declare a label `First Name` which is more user-friendly and can
     * be displayed to end users.
     *
     * By default an attribute label is generated using `generateAttributeLabel()`.
     * This method allows you to explicitly specify attribute labels.
     *
     * Note, in order to inherit labels defined in the parent class, a child class needs to
     * merge the parent labels with child labels using functions such as `array_merge()`.
     *
     * @return array attribute labels (name => label)
     * @see generateAttributeLabel()
     */
    public function attributeLabels(): array
    {
        return [];
    }

    /**
     * Returns the attribute hints.
     *
     * Attribute hints are mainly used for display purpose. For example, given an attribute
     * `isPublic`, we can declare a hint `Whether the post should be visible for not logged in users`,
     * which provides user-friendly description of the attribute meaning and can be displayed to end users.
     *
     * Unlike label hint will not be generated, if its explicit declaration is omitted.
     *
     * Note, in order to inherit hints defined in the parent class, a child class needs to
     * merge the parent hints with child hints using functions such as `array_merge()`.
     *
     * @return array attribute hints (name => hint)
     */
    public function attributeHints(): array
    {
        return [];
    }

    /**
     * Generates a user friendly attribute label based on the give attribute name.
     * This is done by replacing underscores, dashes and dots with blanks and
     * changing the first letter of each word to upper case.
     * For example, 'department_name' or 'DepartmentName' will generate 'Department Name'.
     * @param string $name the column name
     * @return string the attribute label
     */
    public function generateAttributeLabel(string $name): string
    {
        return Inflector::camel2words($name, true);
    }
}