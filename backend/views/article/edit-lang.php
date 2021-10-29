<?php

/** @var \core\web\View $this */
/** @var \customer\models\forms\ArticleForm $model */

use core\activeform\TinyMce;
use core\activeform\TinyMceConfig;

?>
<?= $form->field($model, 'title_' . $lang)->textInput(['autofocus' => true]) ?>
<?= $form->field($model, 'announce_' . $lang)->widget(TinyMce::class, [
    'language' => 'ru',
    'clientOptions' => TinyMceConfig::advanced(['height' => 400]),
])
?>
<?= $form->field($model, 'text_' . $lang)->widget(TinyMce::class, [
    'language' => 'ru',
    'clientOptions' => TinyMceConfig::advanced(['height' => 400]),
])
?>