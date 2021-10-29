<?php

declare(strict_types=1);

namespace core\base;

use core\helpers\ArrayHelper;

/**
 * Class represents specific functions for composite forms.
 */
abstract class ModelFormComposite extends ModelForm
{
    /**
     * @var Model[]|array[]
     */
    private array $_forms = [];

    /**
     * List of internal forms
     * 
     * Example:
     * 
     * ```
     * public function __construct()
     * {
     *      $this->price = new PriceForm();
     *      $this->meta = new MetaForm();
     *      $this->values = new ValueForm();
     * }
     * 
     * protected function internalForms()
     * {
     *      return ['price', 'meta', 'values'];
     * }
     * ```
     * 
     * @return string[] array of internal forms like ['price', 'meta', 'values']
     */
    abstract protected function internalForms(): array;

    /**
     * {@inheritDoc}
     */
    public function fill(?array $data, string $formName = null): bool
    {
        $success = parent::fill($data, $formName);
        foreach ($this->_forms as $name => $form) {
            if (is_array($form)) {
                $success = parent::fillMultiple($form, $data, $formName === null ? null : $name) || $success;
            } else {
                $success = $form->fill($data, $formName !== '' ? null : $name) || $success;
            }
        }
        return $success;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(string|array $attributeNames = null, $clearErrors = true): bool
    {
        if ($attributeNames !== null) {
            $parentNames = array_filter($attributeNames, 'is_string');
            $success = $parentNames ? parent::validate($parentNames, $clearErrors) : true;
        } else {
            $success = parent::validate(null, $clearErrors);
        }
        foreach ($this->_forms as $name => $form) {
            if ($attributeNames === null || array_key_exists($name, $attributeNames) || in_array($name, $attributeNames, true)) {
                $innerNames = ArrayHelper::getValue($attributeNames, $name);
                if (is_array($form)) {
                    $success = parent::validateMultiple($form, $innerNames, $clearErrors) && $success;
                } else {
                    $success = $form->validate($innerNames, $clearErrors) && $success;
                }
            }
        }
        return $success;
    }

    /**
     * {@inheritDoc}
     */
    public function hasErrors(string $attribute = null): bool
    {
        if ($attribute !== null && mb_strpos($attribute, '.') === false) {
            return parent::hasErrors($attribute);
        }
        if (parent::hasErrors($attribute)) {
            return true;
        }
        foreach ($this->_forms as $name => $form) {
            if (is_array($form)) {
                foreach ($form as $i => $item) {
                    if ($attribute === null) {
                        if ($item->hasErrors()) {
                            return true;
                        }
                    } elseif (mb_strpos($attribute, $name . '.' . $i . '.') === 0) {
                        if ($item->hasErrors(mb_substr($attribute, mb_strlen($name . '.' . $i . '.')))) {
                            return true;
                        }
                    }
                }
            } else {
                if ($attribute === null) {
                    if ($form->hasErrors()) {
                        return true;
                    }
                } elseif (mb_strpos($attribute, $name . '.') === 0) {
                    if ($form->hasErrors(mb_substr($attribute, mb_strlen($name . '.')))) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getErrors(string $attribute = null): array
    {
        $result = parent::getErrors($attribute);
        foreach ($this->_forms as $name => $form) {
            if (is_array($form)) {
                /** @var array $form */
                foreach ($form as $i => $item) {
                    foreach ($item->getErrors() as $attr => $errors) {
                        /** @var array $errors */
                        $errorAttr = $name . '.' . $i . '.' . $attr;
                        if ($attribute === null) {
                            foreach ($errors as $error) {
                                $result[$errorAttr][] = $error;
                            }
                        } elseif ($errorAttr === $attribute) {
                            foreach ($errors as $error) {
                                $result[] = $error;
                            }
                        }
                    }
                }
            } else {
                foreach ($form->getErrors() as $attr => $errors) {
                    /** @var array $errors */
                    $errorAttr = $name . '.' . $attr;
                    if ($attribute === null) {
                        foreach ($errors as $error) {
                            $result[$errorAttr][] = $error;
                        }
                    } elseif ($errorAttr === $attribute) {
                        foreach ($errors as $error) {
                            $result[] = $error;
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getFirstErrors(): array
    {
        $result = parent::getFirstErrors();
        foreach ($this->_forms as $name => $form) {
            if (is_array($form)) {
                foreach ($form as $i => $item) {
                    foreach ($item->getFirstErrors() as $attr => $error) {
                        $result[$name . '.' . $i . '.' . $attr] = $error;
                    }
                }
            } else {
                foreach ($form->getFirstErrors() as $attr => $error) {
                    $result[$name . '.' . $attr] = $error;
                }
            }
        }
        return $result;
    }

    public function __get($name)
    {
        return $this->_forms[$name] ?? parent::__get($name);
    }

    public function __set($name, $value)
    {
        if (in_array($name, $this->internalForms(), true)) {
            $this->_forms[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    public function __isset($name)
    {
        return isset($this->_forms[$name]) || parent::__isset($name);
    }
}