<?php

declare(strict_types=1);

namespace common\models;

use core\orm\ActiveRecord;
use core\base\Model;
use core\activeform\ActiveForm;
use core\activeform\ActiveField;
use InvalidArgumentException;

/**
 * @property int $id
 * @property string $name Setting input name.
 * @property mixed $value Setting value.
 * @property string $default Default setting value.
 * @property string $type Setting data type, see TYPE_* constants.
 * @property string $input Setting input type, see INPUT_* constants..
 * @property string $label Setting input label.
 * @property string $hint Setting input hint.
 * @property string $group Setting group.
 * @property int $order Setting order.
 * @property int $status Status
 * @property \Carbon\Carbon $created_at Record creation date.
 * @property \Carbon\Carbon $updated_at Record last update date.
 */
class Setting extends ActiveRecord
{
    const INPUT_TEXT = 'text';

    const INPUT_TEXTAREA = 'textarea';

    const INPUT_CHECKBOX = 'checkbox';

    const INPUT_RADIO = 'radio';


    const TYPE_INT = 'int';

    const TYPE_FLOAT = 'float';

    const TYPE_BOOLEAN = 'bool';

    const TYPE_STRING = 'string';

    const TYPE_EMAIL = 'email';


    protected $table = 'settings';

    protected $fillable = [
        'name',
        'value',
        'default',
        'type',
        'input',
        'label',
        'hint',
        'group',
        'order',
        'status',
    ];

    public function rules(): array
    {
        return [
            [['name', 'type', 'input', 'group'], 'required'],
            ['status', 'integerish'],
            ['name', 'uniqueAttribute' => ['name', $this]],
        ];
    }

    /**
     * Cast setting value according data type.
     *
     * @param string $value
     * @return bool|int|float|string
     */
    public function getValueAttribute(string $value): bool|int|float|string
    {
        return match ($this->type) {
            self::TYPE_BOOLEAN => boolval($value),
            self::TYPE_INT => (int) $value,
            self::TYPE_FLOAT => (float) str_replace(',', '.', $value),
            default => $value
        };
    }

    /**
     * Generate setting form field
     * 
     * @param ActiveForm $form
     * @param Model $model
     * 
     * @return ActiveField
     */
    public function field(ActiveForm $form, Model $model, array $options = []): ActiveField
    {
        $method = $this->getFieldMethod();
        return $form
            ->field($model, $this->name, $options)
            ->$method()
            ->label($this->label)
            ->hint($this->hint);
    }

    /**
     * Determine the `ActiveField` method name depending on input type.
     * 
     * @return string
     */
    private function getFieldMethod(): string
    {
        return match ($this->input) {
            self::INPUT_TEXT => 'textInput',
            self::INPUT_TEXTAREA => 'textarea',
            self::INPUT_CHECKBOX => 'checkbox',
            self::INPUT_RADIO => 'radio',
            default => throw new InvalidArgumentException("Unknown setting input type {$this->type}")
        };
    }
}