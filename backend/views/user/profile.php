<?php

/** @var \core\web\View $this */
/** @var \customer\entities\Employee $model */

use core\widgets\DetailView;
use core\helpers\Url;
use core\helpers\Html;

?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <?= Html::a(
                        '<i class="far fa-fw fa-edit"></i> Редактировать',
                        Url::to(['user/edit', 'id' => $model->id]),
                        ['class' => 'btn btn-sm btn-primary']
                    )
                    ?>
                    <?= Html::a(
                        '<i class="fas fa-fw fa-key"></i> Сменить пароль',
                        Url::to(['user/changepassword', 'id' => $model->id]),
                        ['class' => 'btn btn-sm btn-primary']
                    )
                    ?>
                    <?= Html::a(
                        $model->status->getHelper()->blockButtonText(['Заблокировать', 'Разблокировать']),
                        Url::to(['user/block', 'id' => $model->id, 'operation' => $model->status->getHelper()->blockQueryValue()]),
                        [
                            'class' => 'btn btn-sm btn-danger get-confirm',
                            'data-confirm' => $model->status->getHelper()->blockConfirmQuestion(['заблокировать', 'разблокировать']),
                            'data-method' => 'post',
                            'visible' => Sys::$app->user->identity->id !== $model->id
                        ]
                    ) ?>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <?= DetailView::widget([
                            'model' => $model,
                            'attributes' => [
                                // 'id:text:ID',
                                'username:text:Логин',
                                'fullname:text:Имя',
                                'email:email:Email',
                                [
                                    'attribute' => 'role',
                                    'label' => 'Роль',
                                    'format' => 'raw',
                                    'value' => $model->role
                                ],
                                [
                                    'attribute' => 'status',
                                    'label' => 'Статус',
                                    'format' => 'raw',
                                    'value' => $model->status->getHelper()->statusLabel()
                                ],
                            ],
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>