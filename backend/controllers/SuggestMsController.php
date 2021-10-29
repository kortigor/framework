<?php

declare(strict_types=1);

namespace backend\controllers;

use core\web\ContentType;
use core\exception\HttpException;
use backend\base\BaseBackendController;
use customer\services\SuggestMsService;

class SuggestMsController extends BaseBackendController
{
    /**
     * @var SuggestMsService
     */
    private SuggestMsService $service;

    public function __construct()
    {
        if (!$this->request->isAjax()) {
            throw new HttpException(500, 'Invalid Ajax request');
        }

        $this->response->setFormat(ContentType::FORMAT_JSON);
        $this->service = new SuggestMsService($this->request, false);
        $this->limit = 10;
    }

    public function actionArticle()
    {
        return $this->service->article();
    }

    public function actionNews()
    {
        return $this->service->news();
    }

    public function actionFaq()
    {
        return $this->service->faq();
    }

    public function actionPage()
    {
        return $this->service->page();
    }

    public function actionProduction()
    {
        return $this->service->production();
    }

    public function actionProductionСat()
    {
        return $this->service->productionCat();
    }
}