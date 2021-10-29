<?php

declare(strict_types=1);

namespace backend\controllers;

use common\controllers\BaseSearchSuggestController;
use customer\services\SuggestService;

class SuggestController extends BaseSearchSuggestController
{
    /**
     * @var SuggestService
     */
    private SuggestService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SuggestService($this->request, false, c('main.search.suggestionLength'));
    }

    public function actionSubscribe()
    {
        return $this->service->subscribe($this->limit);
    }

    public function actionSubscribeNews()
    {
        return $this->service->subscribe($this->limit);
    }

    public function actionArticle()
    {
        return $this->service->article($this->limit);
    }

    public function actionNews()
    {
        return $this->service->news($this->limit);
    }

    public function actionFaq()
    {
        return $this->service->faq($this->limit);
    }

    public function actionPage()
    {
        return $this->service->page($this->limit);
    }

    public function actionOrder()
    {
        return $this->service->order($this->limit);
    }

    public function actionProduction()
    {
        return $this->service->production($this->limit);
    }

    public function actionProductionCat()
    {
        return $this->service->productionCat($this->limit);
    }

    public function actionProductionParam()
    {
        return $this->service->productionParam($this->limit);
    }

    public function actionProductionparamIndexUnits()
    {
        return $this->service->productionParamUnit($this->limit);
    }
}
