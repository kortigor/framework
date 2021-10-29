<?php

/** @var \core\web\View $this */
/** @var \customer\models\forms\ArticleCategoryForm $model */

use core\bootstrap4\ActiveForm;
use core\bootstrap4\Tabs;
use core\helpers\Html;
use customer\helpers\I18nHelper;

$form = ActiveForm::begin(['options' => ['id' => 'newsCategoryForm']]);
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?= Tabs::widget([
                    'items' => I18nHelper::getEditTabsItems('articlecat/edit-lang', $model, $form, $this),
                    'encodeLabels' => false,
                    'itemOptions' => ['class' => 'mb-4'],
                ]) ?>
                <div class="row">
                    <div class="col-md-4">
                        <?= $form->field($model, 'status')->dropdownList($model->statusList()) ?>
                        <?= $form->field($model, 'renew_slug', ['visible' => $model->getScenario() === $model::SCENARIO_EDIT])->checkbox() ?>
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
