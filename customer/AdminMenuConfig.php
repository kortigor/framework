<?php

declare(strict_types=1);

namespace customer;

use core\interfaces\ConfigProviderInterface;
use customer\entities\ArticleCategory;

class AdminMenuConfig implements ConfigProviderInterface
{
    public string $byCatIcon = 'arrow-right';

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        // See core\bootstrap4\MenuAdminLTE for more info
        return [

            [
                'label' => 'Библиотека',
                'icon' => 'book',
                'url' => '#',
                'items' => [
                    ['label' => 'Список', 'url' => ['article/']],
                    ['label' => 'По категориям', 'url' => '#', 'items' => $this->getArticleCatItems()],
                    ['label' => 'Категории', 'url' => ['articlecat/'],],
                ]
            ],
            [
                'label' => 'Настройки',
                'icon' => 'cog',
                'url' => '#',
                'items' => [
                    ['label' => 'Сайт', 'url' => ['settings/main'], 'icon' => 'tools'],
                    ['label' => 'Администраторы', 'url' => ['user/'], 'icon' => 'users'],
                    ['label' => 'Диагностика PHP', 'url' => ['settings/diagnose'], 'icon' => 'php', 'iconType' => 'fab'],
                ]
            ],
        ];
    }

    private function getArticleCatItems(): array
    {
        return ArticleCategory::select('id', 'slug')
            ->orderBy('order')
            ->get()
            ->map(
                fn ($item) => $this->buildItem($item->name, $this->byCatIcon, ['article/', 'category' => $item->slug])
            )
            ->toArray();
    }

    private function buildItem(string $label, string $icon, array $url, string $badge = ''): array
    {
        return [
            'label' => $label,
            'icon' => $icon,
            'url' => $url,
            'badge' => $badge ?: false
        ];
    }
}