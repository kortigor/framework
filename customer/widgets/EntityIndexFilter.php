<?php

declare(strict_types=1);

namespace customer\widgets;

use core\bootstrap4\NavLinkButtonGroup;
use core\bootstrap4\Widget;
use customer\helpers\StatusProfileCounter;
use core\helpers\Url;
use core\http\Uri;

/**
 * Entities index filter widget
 */
class EntityIndexFilter extends Widget
{
    public StatusProfileCounter $statusProfile;

    public string $statusQueryParameter = 'status';

    public array $items;

    private array $defaultItems = [
        ['Видимые', 'STATUS_ACTIVE', 'active'],
        ['Cкрытые', 'STATUS_INACTIVE', 'blocked'],
    ];

    private function getItems()
    {
        $currentUri = Uri::withOutQueryValue(Url::$uri, 'page');

        $result = [
            [
                'label' => 'Все' . ' <span class="profiler-count badge badge-info">' . $this->statusProfile->total . '</span>',
                'url' => Url::getRelative(Uri::withOutQueryValue($currentUri, $this->statusQueryParameter)),
                'active' => fn () => !Url::hasQueryValue($currentUri, $this->statusQueryParameter)
            ],
        ];

        foreach ($this->getItemsData() as $item) {
            $label = $item[0];
            $profileAttribute = $item[1];
            $urlParameter = $item[2];

            $result[] = [
                'label' => $label . ' <span class="profiler-count badge badge-info">' . $this->statusProfile->$profileAttribute . '</span>',
                'url' => Url::getRelative(Uri::withQueryValue($currentUri, $this->statusQueryParameter, $urlParameter)),

            ];
        }

        return $result;
    }

    private function getItemsData(): \Generator
    {
        $items = $this->items ?? $this->defaultItems;
        yield from $items;
    }

    /**
     * Renders the widget.
     * 
     * @throws InvalidConfigException
     */
    public function run()
    {
        return NavLinkButtonGroup::widget([
            'items' => $this->getItems(),
            'encodeLabels' => false,
            'options' => [
                'class' => 'btn-group-sm'
            ]
        ]);
    }
}
