<?php

declare(strict_types=1);

namespace customer\models\forms;

use core\base\Model;
use customer\entities\ArticleCategory;
use customer\entities\Status;

class ArticleCategoryForm extends Model
{
    use FormStatusListsTrait;

    const SCENARIO_EDIT = 'edit';

    public $name_ru;
    public $name_en;
    public $status;
    public $renew_slug;

    public function __construct(ArticleCategory $category)
    {
        $this->fill($category->toArray(), '');
    }

    public function normalizators(): array
    {
        return [
            [['name_ru', 'name_en'], 'trim'],
            ['status', 'intval'],
        ];
    }

    public function rules(): array
    {
        return [
            [['name_ru', 'status'], 'required', 'message' => 'Необходимо заполнить'],
            [['name_en'], 'emptyOrString'],
            ['status', 'oneOf' => [array_keys(Status::list())]],
            ['renew_slug', 'booleanVal', 'on' => self::SCENARIO_EDIT],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name_ru' => 'Название категории (Заголовок)',
            'name_en' => 'Category name',
            'status' => 'Отображение',
            'renew_slug' => 'Обновить URL',
        ];
    }

    public function attributeHints(): array
    {
        return [
            'renew_slug' => 'Url контента создается при добавлении, из текста заголовка.
            Отметьте, если в дальнейшем вы изменили заголовок и хотите изменить Url.
            Старая ссылка на контент при этом станет недействительна.',
        ];
    }
}
