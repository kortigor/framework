<?php

/** @var \core\web\View $this */
/** @var \customer\models\forms\ArticleCategoryForm $model */
?>
<?= $form->field($model, 'name_' . $lang)->textInput(['autofocus' => true]) ?>