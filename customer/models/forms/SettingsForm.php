<?php

declare(strict_types=1);

namespace customer\models\forms;

use core\base\Model;
use customer\entities\Setting;
use Illuminate\Database\Eloquent\Collection;

class SettingsForm extends Model
{
    /**
     * Constructor.
     * 
     * @param Collection<Setting> $collection 
     */
    public function __construct(private Collection $collection)
    {
        // Add necessary validators, according setting data and input types
        foreach ($this->collection as $set) {
            // Add 'booleanVal' to all checkbox inputs
            if ($set->input === Setting::INPUT_CHECKBOX) {
                $this->addRule($set->name, ['booleanVal']);
                continue;
            }

            // Add validator according of input data type
            switch ($set->type) {
                case Setting::TYPE_INT:
                    $this->addRule($set->name, ['integerish'], ['message' => 'Необходимо целое число']);
                    break;
                case Setting::TYPE_FLOAT:
                    $this->addNormalizator($set->name, 'normalizeFloat');
                    $this->addRule($set->name, ['regex' => ['/^\-?[0-9\.]+$/']], ['message' => 'Необходимо десятичное число']);
                    break;
                case Setting::TYPE_BOOLEAN:
                    $this->addRule($set->name, ['booleanVal'], ['message' => 'Необходимо булево значение']);
                    break;
                case Setting::TYPE_STRING:
                    $this->addRule($set->name, ['required'], ['message' => 'Необходимо заполнить']);
                    break;
                case Setting::TYPE_EMAIL:
                    $this->addRule($set->name, ['email'], ['message' => 'Необходим корректный email']);
                    break;
            }
        }
    }

    public function __get($prop): string
    {
        // Get original Setting 'value' attribute without casting
        return $this->get($prop)->getAttributes()['value'];
    }

    public function __set($prop, $value)
    {
        $this->get($prop)->value = trim($value);
    }

    /**
     * Override standard parent `attributes()` method
     * 
     * @return array
     */
    public function attributes(): array
    {
        return $this->collection->pluck('name')->toArray();
    }

    /**
     * Get actual form settings collection.
     * 
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->collection;
    }

    /**
     * Save all form's settings.
     * 
     * @return void
     */
    public function save(): void
    {
        foreach ($this->collection as $item) {
            $item->save();
        }
    }

    /**
     * Normalize float fields
     * 
     * @param string $value
     * 
     * @return string
     */
    public function normalizeFloat(string $value): string
    {
        return str_replace(',', '.', $value);
    }

    /**
     * Get record from collection by setting name.
     * 
     * @param mixed $name
     * 
     * @return Setting
     */
    private function get($name): Setting
    {
        return $this->collection->first(fn (Setting $item) => $item->name === $name);
    }
}