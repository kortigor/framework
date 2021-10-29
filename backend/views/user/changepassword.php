<?php

use core\bootstrap4\ActiveForm;
use core\helpers\Html;

/** @var \core\web\View $this */
/** @var \customer\models\forms\EmployeeForm $model */

?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <?php $form = ActiveForm::begin() ?>
                        <?= $form->field($model, 'newPassword1')->passwordInput() ?>
                        <?= $form->field($model, 'newPassword2')->passwordInput() ?>
                        <div class="form-group">
                            <?= Html::submitButton('<i class="far fa-fw fa-save"></i> Сохранить', [
                                'class' => 'btn btn-primary',
                                'name' => 'save-button'
                            ]) ?>
                        </div>
                        <?php ActiveForm::end() ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>