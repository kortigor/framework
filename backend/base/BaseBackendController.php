<?php

declare(strict_types=1);

namespace backend\base;

use core\base\Controller;
use core\exception\HttpException;
use core\web\View;

abstract class BaseBackendController extends Controller
{
    /**
     * @var int Items per page for paginators. Retrieved from request.
     */
    protected int $pageSize;

    public function __construct()
    {
        $this->pageSize = (int) $this->request->get('items', c('site.items_per_page'));
        $this->view = new View('adminLTE');
        $this->view->blocks['flashes'] = $this->flash();
        $this->view->title = c('main.siteTitle');
        if ($csrfName = $this->request->getAttribute('csrfParam')) {
            $this->view->registerCsrfMetaTag($csrfName, $this->request->getAttribute($csrfName));
        }
    }

    public function actionIndex()
    {
        throw new HttpException(404, 'Страница не найдена');
    }
}