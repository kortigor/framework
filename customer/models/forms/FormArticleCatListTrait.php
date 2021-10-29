<?php

declare(strict_types=1);

namespace customer\models\forms;

use customer\entities\{
    ArticleCategory,
    Status
};

/**
 * Visible Article categories list.
 */
trait FormArticleCatListTrait
{
    /**
     * Visible Article categories list.
     * 
     * @return array
     */
    public function articleCategoryList(bool $onlyVisible = false): array
    {
        $condition = $onlyVisible ? ['status' => Status::STATUS_ACTIVE] : [];
        return ArticleCategory::where($condition)
            ->get()
            ->mapWithKeys(fn ($item) => [$item->id => $item->name_ru])
            ->toArray();
    }
}
