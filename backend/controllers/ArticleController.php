<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\base\BaseBackendController;
use common\filters\Search;
use customer\filters\EntityFilter;
use customer\helpers\StatusProfileCounter;
use customer\services\ArticleService;
use customer\entities\Article;
use customer\models\forms\ArticleForm;
use core\web\Breadcrumbs;
use core\helpers\Url;
use core\exception\HttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ArticleController extends BaseBackendController
{
    private ArticleService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ArticleService;
        Breadcrumbs::add('Библиотека', Url::to(['article/']));
        $this->view->setTitle('Библиотека');
    }

    public function actionIndex()
    {
        $filter = new EntityFilter($this->request); // Request query filter
        $search = new Search($this->request, ['translate.title', 'translate.announce']); // Request search query filter

        $collection = Article::filter($search)
            ->filter($filter)
            ->orderBy('order')
            ->paginate($this->pageSize); // Filtered collection

        $statusProfile = new StatusProfileCounter(Article::class); // Collection counted by status
        $filterStatus = clone $filter;
        $statusProfile->addFilter($filterStatus->disable('status'));

        return $this->view
            ->assign('collection', $collection)
            ->assign('statusProfile', $statusProfile)
            ->render('article/index');
    }

    public function actionEdit()
    {
        $article = $this->findItem();
        $model = new ArticleForm($article);
        $model->setScenario(ArticleForm::SCENARIO_EDIT);

        if ($model->fill($this->request->post()) && $model->validate()) {
            $this->service->save($article, $model);
            $this->redirect(['article/']);
        } else {
            $this->view->setTitle($article->title_ru . ' : Редактировать статью');
            Breadcrumbs::add('Редактировать статью : ' . $article->title_ru, Url::current());
            return $this->view->render('article/edit', compact('article', 'model'));
        }
    }

    public function actionCreate()
    {
        $article = Article::buildEmpty();
        $model = new ArticleForm($article);
        try {
            $category = $this->service->getCategoryBySlug($this->request->get('slug', ''));
            $model->category_id = $category->id;
        } catch (ModelNotFoundException $e) {
        }

        if ($model->fill($this->request->post()) && $model->validate()) {
            $this->service->add($article, $model);
            $this->redirect(['article/']);
        } else {
            $this->view->setTitle('Добавить статью');
            Breadcrumbs::add('Добавить статью', Url::current());
            return $this->view->render('article/edit', compact('article', 'model'));
        }
    }

    public function actionDelete()
    {
        $model = $this->findItem('post');
        $this->service->remove($model);
        $this->redirect($this->request->getReferrer());
    }

    public function actionBlock()
    {
        $model = $this->findItem('post');
        $operation = $this->request->post('operation');

        if (in_array($operation, ['block', 'unblock'])) {
            $this->service->$operation($model);
        }

        $this->redirect($this->request->getReferrer());
    }

    public function actionOrder()
    {
        if (!$this->request->isAjax()) {
            throw new HttpException(500, 'Invalid sorting request');
        }

        $this->service->updateOrder($this->request->post('sort'), Article::class);
        return t('Порядок сортировки изменен');
    }

    private function findItem(string $method = 'get'): Article
    {
        try {
            return $this->service->get($this->request->$method('id', ''));
        } catch (ModelNotFoundException $e) {
            throw new HttpException(404, 'Статья не найдена', 0, $e);
        }
    }
}
