<?php

declare(strict_types=1);

namespace frontend\controllers;

use frontend\base\BaseFrontendController;
use core\web\Breadcrumbs;
use core\helpers\Url;
use customer\services\SearchService;

class SearchController extends BaseFrontendController
{
    /**
     * @var SearchService
     */
    protected SearchService $service;

    /**
     * @var array SearchService methods to use and their sequence in the search result.
     */
    protected array $config = [
        'article',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->view->setTitle(t('Поиск'));
        $this->service = new SearchService($this->request, true);
        Breadcrumbs::add(t('Поиск'), Url::to(['search/']));
    }

    public function actionIndex()
    {
        if ($this->request->get('search')) {
            foreach ($this->config as $method) {
                $sub = $this->service->$method();
                if ($sub->isNotEmpty()) {
                    $collection[] = $sub;
                }
            }
        }

        return $this->view
            ->assign('collection', $collection ?? [])
            ->render('search/index');
    }
}