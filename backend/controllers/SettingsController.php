<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\base\BaseBackendController;
use customer\services\SettingService;
use customer\entities\Currency;
use customer\models\forms\SettingsForm;
use core\web\Breadcrumbs;
use core\helpers\Url;

class SettingsController extends BaseBackendController
{
    private SettingService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SettingService;
        $this->view->setTitle('Настройки');
    }

    public function actionMain()
    {
        $collection = $this->service->getGroup('main');
        $model = new SettingsForm($collection);

        if ($model->fill($this->request->post()) && $model->validate()) {
            $model->save();
            $this->flash()->set('info', 'Настройки изменены');
            $this->redirect(['settings/main']);
        } else {
            Breadcrumbs::add('Настройки сайта', Url::current());
            return $this->view->render('settings/site-main', compact('model'));
        }
    }

    public function actionCurrency()
    {
        $collection = $this->service->getGroup('currency');
        $model = new SettingsForm($collection);

        if ($model->fill($this->request->post()) && $model->validate()) {
            $model->save();
            $this->flash()->set('info', 'Настройки изменены');
            $this->redirect(['settings/currency']);
        } else {
            $currency = Currency::all();
            Breadcrumbs::add('Настройки валюты', Url::current());
            return $this->view->render('settings/currency', compact('model', 'currency'));
        }
    }

    public function actionDiagnose()
    {
        phpinfo();
    }
}
