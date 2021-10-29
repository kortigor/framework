<?php

declare(strict_types=1);

namespace frontend\controllers;

use customer\ContentUrl;
use frontend\base\BaseFrontendController;
use customer\services\ArticleService;
use customer\entities\Article;
use customer\entities\Status;
use customer\entities\ArticleCategory;
use core\web\Breadcrumbs;
use core\helpers\Url;
use core\exception\HttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Builder;

class ArticleController extends BaseFrontendController
{
    private ArticleService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ArticleService();
        Breadcrumbs::add(t('Библиотека'), Url::to(['article/']));
        $this->view->setTitle(t('Библиотека'));
    }

    public function actionIndex()
    {
        $collection = Article::whereHas('category', fn (Builder $query) => $query->where('status', Status::STATUS_ACTIVE))
            ->where('status', Status::STATUS_ACTIVE)
            ->orderBy('created_at', 'desc')
            ->paginate($this->pageSize); // Paginated Collection

        return $this->view
            ->assign('collection', $collection)
            ->render('articles/index');
    }

    /**
     * View articles
     * 
     * @param string $slug
     * 
     * @return mixed
     */
    public function actionArticle(string $slug)
    {
        $model = $this->findItem($slug);
        $this->view->prependToTitle($model->title . ' : ');
        Breadcrumbs::add($model->category->name, ContentUrl::to($model->category));
        Breadcrumbs::add($model->title, URL::current());
        $this->view->assign('model', $model);
        return $this->view->render('articles/article');
    }

    /**
     * View articles category
     * 
     * @param string $slug
     * 
     * @return mixed
     */
    public function actionCategory(string $slug)
    {
        $model = $this->findCategory($slug);
        $collection = Article::where('status', Status::STATUS_ACTIVE)
            ->where('category_id', $model->id)
            ->orderBy('order')
            ->paginate($this->pageSize); // Paginated Collection

        Breadcrumbs::add($model->name, '');
        $this->view->prependToTitle($model->name . ' : ');
        return $this->view
            ->assign('collection', $collection)
            ->render('articles/index');
    }

    private function findItem(string $slug): Article
    {
        try {
            return $this->service->getBySlug($slug);
        } catch (ModelNotFoundException $e) {
            throw new HttpException(404, 'Статья не найдена', 0, $e);
        }
    }

    private function findCategory(string $slug): ArticleCategory
    {
        try {
            return $this->service->getCategoryBySlug($slug);
        } catch (ModelNotFoundException $e) {
            throw new HttpException(404, 'Категория не найдена', 0, $e);
        }
    }
}
