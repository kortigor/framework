<?php

declare(strict_types=1);

namespace customer\models\forms;

use core\base\Model;
use customer\entities\Article;
use customer\entities\Status;

class ArticleForm extends Model
{
    use FormStatusListsTrait;
    use FormArticleCatListTrait;

    const SCENARIO_EDIT = 'edit';

    public $title_ru;
    public $title_en;
    public $announce_ru;
    public $announce_en;
    public $text_ru;
    public $text_en;
    public $allow_comments;
    public $status;
    public $category_id;
    public $renew_slug;

    public function __construct(Article $article)
    {
        $this->fill($article->toArray(), '');
    }

    public function normalizators(): array
    {
        return [
            [['title_ru', 'title_en', 'announce_ru', 'announce_en', 'text_ru', 'text_en'], 'trim'],
            [['status', 'allow_comments', 'renew_slug'], 'intval'],
        ];
    }

    public function rules(): array
    {
        return [
            [['title_ru', 'announce_ru', 'text_ru', 'category_id', 'status'], 'required', 'message' => 'Необходимо заполнить'],
            [['title_en', 'announce_en', 'text_en'], 'emptyOrString'],
            ['status', 'oneOf' => [array_keys(Status::list())]],
            ['category_id', 'Uuid'],
            ['allow_comments', 'booleanVal'],
            ['renew_slug', 'booleanVal', 'on' => self::SCENARIO_EDIT],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'status' => 'Отображение',
            'category_id' => 'Категория',
            'renew_slug' => 'Обновить URL',
            'allow_comments' => 'Разрешить комментарии',
            'announce_ru' => 'Анонс',
            'announce_en' => 'Announce',
            'title_ru' => 'Заголовок',
            'title_en' => 'Header',
            'text_ru' => 'Полный текст',
            'text_en' => 'Full text',
        ];
    }

    public function attributeHints(): array
    {
        return [
            'renew_slug' => 'При создании контента его URL создается из текста заголовка.
            Отметьте этот чекбокс, если в дальнейшем, редактируя, вы изменили заголовок и хотите изменить URL контента.
            Старая ссылка на контент при этом станет недействительна.',
        ];
    }
}
