<?php

declare(strict_types=1);

namespace common\controllers;

use core\base\Controller;
use core\web\ContentType;
use core\exception\HttpException;

class BaseSearchSuggestController extends Controller
{
    /**
     * @var string Suggestion query
     */
    protected string $query;

    /**
     * @var int Suggests limit
     */
    protected int $limit;

    public function __construct()
    {
        if (!$this->request->isAjax()) {
            throw new HttpException(500, 'Invalid Ajax request');
        }

        $this->response->setFormat(ContentType::FORMAT_JSON);
        $this->query = $this->request->get('query', '');
        $this->limit = c('main.search.suggestsLimit') ?? 10;
    }
}