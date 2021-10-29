<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\base\BaseBackendController;
use customer\filters\EntityFilter;
use customer\helpers\StatusProfileCounter;
use customer\services\ArticleService;
use customer\entities\ArticleCategory;
use customer\models\forms\ArticleCategoryForm;
use core\web\Breadcrumbs;
use core\helpers\Url;
use core\exception\HttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ArticleCatController extends BaseBackendController
{
    private ArticleService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ArticleService;
        Breadcrumbs::add('Библиотека', Url::to(['article/']));
        Breadcrumbs::add('Категории', Url::to(['articlecat/']));
        $this->view->setTitle('Библиотека');
    }

    public function actionIndex()
    {
        $filter = new EntityFilter($this->request); // Request query filter
        $collection = ArticleCategory::filter($filter)
            ->orderBy('order')
            ->paginate($this->pageSize); // Filtered collection

        $statusProfile = new StatusProfileCounter(ArticleCategory::class); // Collection counted by status

        return $this->view
            ->assign('collection', $collection)
            ->assign('statusProfile', $statusProfile)
            ->render('articlecat/index');
    }

    public function actionCreate()
    {
        $category = ArticleCategory::buildEmpty();
        $model = new ArticleCategoryForm($category);
        if ($model->fill($this->request->post()) && $model->validate()) {
            $this->service->addCategory($category, $model);
            $this->redirect(['articlecat/']);
        } else {
            $this->view->setTitle('Добавить категорию статей');
            Breadcrumbs::add('Добавить новую категорию', Url::current());
            return $this->view->render('articlecat/edit', compact('category', 'model'));
        }
    }

    public function actionEdit()
    {
        $category = $this->findItem();
        $model = new ArticleCategoryForm($category);
        $model->setScenario(ArticleCategoryForm::SCENARIO_EDIT);

        if ($model->fill($this->request->post()) && $model->validate()) {
            $this->service->addCategory($category, $model);
            $this->redirect(['articlecat/']);
        } else {
            $this->view->setTitle('Редактировать категорию статей');
            Breadcrumbs::add('Редактировать категорию', Url::current());
            return $this->view->render('articlecat/edit', compact('category', 'model'));
        }
    }

    public function actionDelete()
    {
        $model = $this->findItem('post');
        $this->service->removeCategory($model);
        $this->redirect($this->request->getReferrer());
    }

    public function actionBlock()
    {
        $model = $this->findItem('post');
        $operation = $this->request->post('operation');

        if (in_array($operation, ['block', 'unblock'])) {
            $operation .= 'Category';
            $this->service->$operation($model);
        }
        $this->redirect($this->request->getReferrer());
    }

    public function actionOrder()
    {
        if (!$this->request->isAjax()) {
            throw new HttpException(500, 'Invalid sorting request');
        }

        $this->service->updateOrder($this->request->post('sort'), ArticleCategory::class);
        return t('Порядок сортировки изменен');
    }

    private function findItem(string $method = 'get'): ArticleCategory
    {
        try {
            return $this->service->getCategory($this->request->$method('id'));
        } catch (ModelNotFoundException $e) {
            throw new HttpException(404, 'Категория не найдена', 0, $e);
        }
    }
}
