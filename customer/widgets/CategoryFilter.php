<?php

declare(strict_types=1);

namespace customer\widgets;

use core\bootstrap4\Widget;
use core\helpers\Url;
use core\helpers\Html;
use Illuminate\Database\Eloquent\Collection;

/**
 * Entities index filter by category widget
 */
class CategoryFilter extends Widget
{
    public string $categoryModelClass;

    public string $placeholder = 'Категория ...';

    public array $options = [];

    public bool $asTree = false;

    private function getItems()
    {
        $collection = $this->categoryModelClass::get()
            ->mapWithKeys(fn ($model) => [$model->slug => $model->name])
            ->toArray();

        return $collection;
    }

    private function getItemsAsTree()
    {
        $collection = $this->categoryModelClass::with('children')
            ->where('parent_id', '')
            ->orderBy('order')
            ->get();

        return $this->buildTree($collection);
    }

    private function buildTree(Collection $collection, int $level = 0)
    {
        $result = [];
        foreach ($collection as $model) {
            $label = str_repeat('--', $level) . ' ' . $model->name;
            $result[$model->slug] = trim($label);
            if ($model->children->isNotEmpty()) {
                $result = array_merge($result, $this->buildTree($model->children, $level + 1));
            }
        }

        return $result;
    }

    /**
     * Renders the widget.
     * 
     * @throws InvalidConfigException
     */
    public function run()
    {
        $this->registerScript();
        parse_str(Url::$uri->getQuery(), $query);

        Html::addCssClass($this->options, 'input-group');
        $items = [0 => '-- Без фильтра --'] + ($this->asTree ? $this->getItemsAsTree() : $this->getItems());

        return '<div class="' . $this->options['class'] . '">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-filter"></i></span>
        </div>' .
            Html::dropDownList(
                'cat-filter',
                $query['category'] ?? null,
                $items,
                [
                    'id' => 'category-filter',
                    'class' => 'form-control',
                    'placeholder' => $this->placeholder,
                ]
            ) . '
        </div>';
    }

    public function registerScript()
    {
        $this->getView()->registerJs("jQuery('html').on('change', '#category-filter', function () {
            let current = window.location.href,
                val = $(this).val();
            window.location.href = val === '0'
                ? removeQueryValue(current, 'category')
                : updateQueryValue(current, 'category', val);
        });");
    }
}
