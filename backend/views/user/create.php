<?php

/** @var \core\web\View $this */
/** @var \customer\models\forms\EmployeeForm $model */

use core\bootstrap4\ActiveForm;
use core\helpers\Html;
use core\activeform\MaskedInput;

?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>
                <?= $form->field($model, 'username')->textInput(['autofocus' => true]) ?>
                <?= $form->field($model, 'fullname')->textInput() ?>
                <?= $form->field($model, 'email')
                    ->widget(MaskedInput::class, [
                        'clientOptions' => ['alias' => 'email'],
                    ]) ?>
                <?= $form->field($model, 'role')->dropdownList($model->roleList()) ?>
                <?= $form->field($model, 'status')->dropdownList($model->statusList()) ?>
                <?= $form->field($model, 'newPassword1')->passwordInput()->label('Пароль') ?>
                <?= $form->field($model, 'newPassword2')->passwordInput()->label('Повторите пароль') ?>
                <div class="form-group">
                    <?= Html::submitButton('<i class="far fa-fw fa-save"></i> Добавить', [
                        'class' => 'btn btn-primary',
                        'name' => 'save-button'
                    ]) ?>
                </div>
                <?php ActiveForm::end() ?>
            </div>
        </div>
    </div>
</div>