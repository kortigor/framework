<?php

/** @var \core\web\View $this */
/** @var \customer\models\forms\EmployeeForm $model */
/** @var \customer\entities\Employee $user */

use core\bootstrap4\ActiveForm;
use core\activeform\MaskedInput;
use core\helpers\Html;

?>
<?php $form = ActiveForm::begin(['options' => ['id' => 'employeeForm']]) ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?= $form->field($model, 'username')->textInput(['autofocus' => true]) ?>
                <?= $form->field($model, 'fullname')->textInput() ?>
                <?= $form->field($model, 'email')
                    ->widget(MaskedInput::class, [
                        'clientOptions' => ['alias' => 'email'],
                    ]) ?>
                <?= $form->field($model, 'role')->dropdownList($model->roleList()) ?>
                <?= $form->field($model, 'status')->dropdownList(
                    $model->statusList(),
                    ['disabled' => $model->user->id === Sys::$app->user->identity->id]
                ) ?>
                <div class="form-group">
                    <?= Html::submitButton('<i class="far fa-fw fa-save"></i> Сохранить', [
                        'class' => 'btn btn-primary',
                        'name' => 'save-button',
                    ]) ?>
                </div>

            </div>
        </div>
    </div>
</div>
<?php ActiveForm::end() ?>