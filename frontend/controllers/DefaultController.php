<?php

declare(strict_types=1);

namespace frontend\controllers;

use frontend\base\BaseFrontendController;

class DefaultController extends BaseFrontendController
{
    public function actionIndex()
    {
        return $this->view->render('index');
    }
}