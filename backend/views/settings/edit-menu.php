<?php

/** @var \core\web\View $this */
/** @var \customer\models\forms\CommentForm $model */

use core\bootstrap4\ActiveForm;
use core\helpers\{
    Html
};
?>
<?php $form = ActiveForm::begin(['enableClientValidation' => false, 'options' => ['id' => 'menuForm']]) ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6">
                        <?= $form->field($model, 'label_ru')->textInput(['autofocus' => true]) ?>
                    </div>
                    <div class="col-sm-6">
                        <?= $form->field($model, 'label_en')->textInput() ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <?= $form->field($model, 'url')->textInput() ?>
                    </div>
                    <div class="col-sm-6">
                        <?= $form->field($model, 'icon')->textInput() ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4">
                        <?= $form->field($model, 'status')->dropdownList($model->statusList()) ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4">
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
    </div>
</div>
<?php
ActiveForm::end();
