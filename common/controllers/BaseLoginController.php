<?php

declare(strict_types=1);

namespace common\controllers;

use Sys;
use core\base\Controller;
use core\auth\AuthAgentByCookie;
use core\web\View;
use common\models\LoginForm;

abstract class BaseLoginController extends Controller
{
    public function __construct()
    {
        $this->view = new View('main');

        if ($csrfName = $this->request->getAttribute('csrfParam')) {
            $this->view->registerCsrfMetaTag($csrfName, $this->request->getAttribute($csrfName));
        }
    }

    public function actionIndex()
    {
        if (isAuth()) {
            return $this->redirect(c('main.homeUrl'));
        }

        $model = new LoginForm;

        if ($model->fill($this->request->post()) && $model->login()) {
            $model->user->save();
            $authDuration = $model->rememberme
                ? c('main.auth.rememberDuration')
                : c('main.auth.loginDuration');

            $agent = AuthAgentByCookie::fromIdentity($model->user, c('main.auth.identityCookieName'));
            $agent->setDuration($authDuration);
            $this->response->cookies->add($agent->getAuthCookie(), true);

            $redirectUrl =
                $this->request->cookies->getValue(c('main.auth.returnUrlCookieName'))
                ?? c('main.homeUrl');

            $this->redirect($redirectUrl);
        } else {
            $model->password = '';
            $this->view->assign('model', $model);
            return $this->view->render('/common/views/login');
        }
    }

    public function actionLogout()
    {
        if (isGuest()) {
            return $this->redirect(c('main.homeUrl'));
        }

        Sys::$app->user->logout();
        $this->response->cookies->removeByName(c('main.auth.identityCookieName'));
        $redirectUrl = $this->request->getReferrer() ?? c('main.homeUrl');
        $this->redirect($redirectUrl);
    }
}
