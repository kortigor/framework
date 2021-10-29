<?php

declare(strict_types=1);

namespace common\models;

use core\base\Model;

class SearchForm extends Model
{
    public $search = '';

    public function normalizators(): array
    {
        return [
            [['search'], 'trim'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'search' => 'Поиск',
        ];
    }
}