<?php

declare(strict_types=1);

namespace customer\services;

use core\validators\Assert;
use core\orm\ActiveRecord;
use common\services\BaseService;
use customer\entities\Status;
use customer\entities\Article;
use customer\entities\ArticleCategory;
use customer\models\forms\ArticleForm;
use customer\models\forms\ArticleCategoryForm;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ArticleService extends BaseService
{
    /**
     * Find Article by condition
     * 
     * @param array $condition
     * 
     * @return Article
     * @throws ModelNotFoundException if Article with given condition is not found
     */
    protected function findByCondition(array $condition): Article
    {
        return Article::where($condition)->firstOrFail();
    }

    /**
     * Find Article category by condition
     * 
     * @param array $condition
     * 
     * @return Article
     * @throws ModelNotFoundException if Article category with given condition is not found
     */
    protected function findCategoryByCondition(array $condition): ArticleCategory
    {
        return ArticleCategory::where($condition)->firstOrFail();
    }

    /**
     * Get Article by id
     * 
     * @param string $id Article id
     * 
     * @return Article
     * @throws ModelNotFoundException if Article with given id is not exists
     */
    public function get(string $id): Article
    {
        return $this->findByCondition([['id', $id]]);
    }

    /**
     * Get Article by slug
     * 
     * @param string $slug Article slug
     * 
     * @return Article
     * @throws ModelNotFoundException if Article with given slug is not exists
     */
    public function getBySlug(string $slug): Article
    {
        return $this->findByCondition([['slug', $slug]]);
    }

    /**
     * Get Article category by id
     * 
     * @param string $id Article category id
     * 
     * @return ArticleCategory
     * @throws ModelNotFoundException if Article category with given id is not exists
     */
    public function getCategory(string $id): ArticleCategory
    {
        return $this->findCategoryByCondition([['id', $id]]);
    }

    /**
     * Get Article category by slug
     * 
     * @param string $slug Article category slug
     * 
     * @return ArticleCategory
     * @throws ModelNotFoundException if Article category with given slug is not exists
     */
    public function getCategoryBySlug(string $slug): ArticleCategory
    {
        return $this->findCategoryByCondition([['slug', $slug]]);
    }

    /**
     * Get only active Article by id
     * 
     * @param string $id Article id
     * 
     * @return Article
     * @throws ModelNotFoundException if active Article with given id is not exists
     */
    public function getActive(string $id): Article
    {
        return $this->findByCondition([
            ['id', $id],
            ['status', Status::STATUS_ACTIVE]
        ]);
    }

    /**
     * Add Article
     * 
     * @param Article $item
     * @param ArticleForm $form
     * 
     * @return void
     */
    public function add(Article $item, ArticleForm $form): void
    {
        $this->save($item, $form);
    }

    /**
     * Add Article category
     * 
     * @param Article $item
     * @param ArticleForm $form
     * 
     * @return void
     */
    public function addCategory(ArticleCategory $category, ArticleCategoryForm $form): void
    {
        $this->saveCategory($category, $form);
    }

    /**
     * Save Article
     * 
     * @param Article $item
     * @param ArticleForm $form
     * 
     * @return void
     */
    public function save(Article $item, ArticleForm $form): void
    {
        $item->fill($form->toArray());

        // Set slug to null to auto create after save
        if ($form->renew_slug) {
            $item->slug = null;
        }

        $item->saveAggregate();
    }

    /**
     * Save Article category
     * 
     * @param ArticleCategory $category
     * @param ArticleCategoryForm $form
     * 
     * @return void
     */
    public function saveCategory(ArticleCategory $category, ArticleCategoryForm $form): void
    {
        $category->fill($form->toArray(), '');

        // Set slug to null to auto create after save
        if ($form->renew_slug) {
            $category->slug = null;
        }

        $category->saveAggregate();
    }

    /**
     * Remove Article
     * 
     * @param Article $item
     * 
     * @return void
     */
    public function remove(Article $item): void
    {
        $item->delete();
    }

    /**
     * Remove Article category
     * 
     * @param ArticleCategory $category
     * 
     * @return void
     */
    public function removeCategory(ArticleCategory $category): void
    {
        $category->delete();
    }

    /**
     * Block Article
     * 
     * @param Article $item
     * 
     * @return void
     */
    public function block(Article $item): void
    {
        $item->block();
        $item->saveAggregate();
    }

    /**
     * Unblock Article
     * 
     * @param Article $item
     * 
     * @return void
     */
    public function unblock(Article $item): void
    {
        $item->unBlock();
        $item->saveAggregate();
    }

    /**
     * Block Article Category
     * 
     * @param ArticleCategory $category
     * 
     * @return void
     */
    public function blockCategory(ArticleCategory $category): void
    {
        $category->block();
        foreach ($category->article as $item) {
            $this->block($item);
        }
        $category->saveAggregate();
    }

    /**
     * Unblock Article Category
     * 
     * @param ArticleCategory $category
     * 
     * @return void
     */
    public function unblockCategory(ArticleCategory $category): void
    {
        $category->unBlock();
        foreach ($category->article as $item) {
            $this->unblock($item);
        }
        $category->saveAggregate();
    }

    /**
     * Update sorting order.
     * 
     * @param string[] $items Items sorting data. Ids in desired order [$id1, $id2...]
     * 
     * @return void
     * @throws InvalidArgumentException If:
     *  - $items param array is associative;
     *  - any id is invalid;
     *  - given class not ActiveRecord.
     */
    public function updateOrder(array $items, string $class): void
    {
        foreach ($items as $position => $id) {
            Assert::integer($position);
            Assert::uuid($id);
            Assert::isAOf($class, ActiveRecord::class);
            $class::where('id', $id)->update(['order' => $position + 1]);
        }
    }
}
