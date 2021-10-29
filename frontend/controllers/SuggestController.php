<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\controllers\BaseSearchSuggestController;
use customer\services\SuggestService;

class SuggestController extends BaseSearchSuggestController
{
    /**
     * @var int Suggests config
     * @see SuggestService::compose()
     */
    private array $config;

    /**
     * @var SuggestService
     */
    private SuggestService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SuggestService($this->request, true, c('main.search.suggestionLength'));
        $this->config = [
            'article' => ['keys' => ['description' => t('Библиотека')]],
        ];
    }

    public function actionSiteMain(): array
    {
        return $this->service->compose($this->limit, $this->config);
    }
}