<?php

declare(strict_types=1);

namespace common\widgets;

use core\widgets\Widget;
use core\helpers\Json;
use common\assets\JquerySortableAsset;

/**
 * Sortable table based on Jquery sortable
 */
class SortableTable extends Widget
{
    /**
     * @var string JQuery sortable container selector.
     */
    public string $selector = '#sort-items';

    /**
     * @var string JQuery selector of containers with items index numbers.
     * Used to update numbers after sorting.
     */
    public string $numberSelector = '.num';

    /**
     * @var string Sortable handler url.
     */
    public string $url;

    /**
     * @var string Sortable items ID format (only 'uuid' does matter).
     */
    public string $idFormat = '';

    /**
     * @var string Default options for Jquery UI sortable.
     * @see https://jqueryui.com/sortable/ Jquery UI sortable options
     */
    public array $sortableDefaultOptions = [
        'handle' => '.sort-handle',
        'placeholder' => 'sort-placeholder',
        'connectWith' => '.sort-connected',
        'forcePlaceholderSize' => true,
        'scroll' => true,
        'axis' => 'y',
    ];

    /**
     * @var string Options for Jquery sortable.
     */
    public array $sortableOptions = [];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        JquerySortableAsset::register($this->getView());
    }

    public function getOptions(): array
    {
        $sortableOptions = array_merge($this->sortableDefaultOptions, $this->sortableOptions);
        $options = [
            'selector' => $this->selector,
            'numberSelector' => $this->numberSelector,
            'url' => $this->url,
            'idFormat' => $this->idFormat,
            'sortableOptions' => $sortableOptions
        ];
        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->getView()->registerJs("let sortable = new SortableTable(" . Json::htmlEncode($this->getOptions()) . ");\nsortable.sort();");
    }
}