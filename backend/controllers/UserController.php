<?php

declare(strict_types=1);

namespace backend\controllers;

use Sys;
use backend\base\BaseBackendController;
use customer\filters\EntityFilter;
use customer\helpers\StatusProfileCounter;
use customer\entities\Employee;
use customer\models\forms\EmployeeForm;
use customer\services\EmployeeService;
use core\web\Breadcrumbs;
use core\helpers\Url;
use core\exception\HttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class UserController extends BaseBackendController
{
    private EmployeeService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new EmployeeService;
        Breadcrumbs::add('Администраторы', Url::to(['user/']));
        $this->view->setTitle('Управление администраторами');
    }

    public function actionIndex()
    {
        $filter = new EntityFilter($this->request); // Request query filter
        $collection = Employee::filter($filter)
            ->orderBy('created_at')
            ->paginate($this->pageSize); // Filtered Employees collection

        $statusProfile = new StatusProfileCounter(Employee::class); // Employees counted by status

        return $this->view
            ->assign('collection', $collection)
            ->assign('statusProfile', $statusProfile)
            ->render('user/index');
    }

    public function actionProfile()
    {
        $model = $this->findItem();
        $this->view->setTitle($model->fullname . ': Профиль пользователя');
        Breadcrumbs::add($model->fullname, URL::current());
        return $this->view->render('user/profile', compact('model'));
    }

    public function actionCreate()
    {
        $user = Employee::buildEmpty();
        $model = new EmployeeForm($user);
        $model->setScenario(EmployeeForm::SCENARIO_ADMIN_CREATE);

        if ($model->fill($this->request->post()) && $model->validate()) {
            $this->service->add($user, $model);
            $this->redirect(['user/profile', 'id' => $user->id]);
        } else {
            $this->view->setTitle('Добавить пользователя');
            Breadcrumbs::add('Добавить пользователя', URL::current());
            return $this->view->render('user/create', compact('model'));
        }
    }

    public function actionEdit()
    {
        $user = $this->findItem();
        $model = new EmployeeForm($user);
        $model->setScenario(EmployeeForm::SCENARIO_ADMIN_UPDATE);

        if ($model->fill($this->request->post()) && $model->validate()) {
            $this->service->save($user, $model);
            $this->redirect(['user/profile', 'id' => $user->id]);
        } else {
            $this->view->setTitle($user->fullname . ': Редактировать профиль');
            Breadcrumbs::add($user->fullname, URL::to(['user/profile', 'id' => $user->id]));
            Breadcrumbs::add('Редактировать профиль', URL::current());
            return $this->view->render('user/edit', compact('user', 'model'));
        }
    }

    public function actionChangepassword()
    {
        $user = $this->findItem();
        $model = new EmployeeForm($user);
        $model->setScenario(EmployeeForm::SCENARIO_ADMIN_PASSWORD_CHANGE);

        if ($model->fill($this->request->post()) && $model->validate()) {
            $this->service->changePassword($user, $model);
            $this->redirect(['user/profile', 'id' => $user->id]);
        } else {
            $this->view->setTitle($user->fullname . ': Сменить пароль');
            Breadcrumbs::add($user->fullname, URL::to(['user/profile', 'id' => $user->id]));
            Breadcrumbs::add('Сменить пароль', URL::current());
            return $this->view->render('user/changepassword', compact('user', 'model'));
        }
    }

    public function actionBlock()
    {
        $user = $this->findItem('post');
        if (Sys::$app->user->identity->id === $user->id) {
            throw new HttpException(403, 'Нельзя заблокировать свою учетную запись.');
        }

        $operation = $this->request->post('operation');
        if (in_array($operation, ['block', 'unblock'])) {
            $this->service->$operation($user);
        }
        $this->redirect(['user/profile', 'id' => $user->id]);
    }

    private function findItem(string $method = 'get'): Employee
    {
        try {
            return $this->service->get($this->request->$method('id', ''));
        } catch (ModelNotFoundException $e) {
            throw new HttpException(404, 'Пользователь не найден', 0, $e);
        }
    }
}
