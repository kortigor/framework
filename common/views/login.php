<?php

/** @var \core\web\View $this */
/** @var \common\models\LoginForm $model */

use core\bootstrap4\{
    ActiveForm,
    Html
};

$this->title = 'Пожалуйста зарегистрируйтесь';
?>
<div class="row align-items-center vh-100">
    <div class="col-md-3 mx-auto">
        <?php $form = ActiveForm::begin(['options' => ['class' => 'form-signin submit-default']]) ?>
        <h1 class="h4 my-3 font-weight-normal">Пожалуйста зарегистрируйтесь</h1>
        <?= $form->field($model, 'username')->textInput(['autofocus' => true]) ?>
        <?= $form->field($model, 'password')->passwordInput() ?>
        <?= $form->field($model, 'rememberme')->checkbox() ?>
        <div class="form-group">
            <?= Html::submitButton('<i class="fas fa-fw fa-sign-in-alt"></i> Войти', [
                'class' => 'btn btn-primary btn-block',
                'name' => 'login-button'
            ]) ?>
        </div>
        <?php ActiveForm::end() ?>
    </div>
</div>