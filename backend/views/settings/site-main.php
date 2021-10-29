<?php

/** @var \core\web\View $this */
/** @var \customer\models\forms\SettingsForm $model */
/** @var \customer\entities\Setting $set */

use core\bootstrap4\ActiveForm;
use core\helpers\Html;
?>
<?php $form = ActiveForm::begin(['options' => ['id' => 'settingsForm']]) ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-8">
                        <?php foreach ($model->collection() as $set) : ?>
                            <?= $set->field($form, $model) ?>
                        <?php endforeach ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
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
