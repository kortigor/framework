<?php

declare(strict_types=1);

namespace frontend\panels;

use customer\ContentUrl;
use core\base\Panel;
use core\widgets\SideNav;
use customer\entities\ArticleCategory;
use customer\entities\Status;

/**
 * Меню "Библиотека"
 */
class LibraryMenu extends Panel
{
    function render(): string
    {
        $collection = ArticleCategory::where('status', Status::STATUS_ACTIVE)->orderBy('order')->get();

        $items = [];
        /** @var ArticleCategory $item */
        foreach ($collection as $item) {
            $items[] = [
                'url' => ContentUrl::to($item),
                'label' => $item->name,
            ];
        }

        return SideNav::widget([
            'items' => $items,
            'itemOptions' => ['class' => 'border-bottom-0'],
            'linkTemplate' => '<a href="{url}">{icon}{label}</a>',
        ]);
    }
}
