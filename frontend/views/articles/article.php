<?php

/** @var \core\web\View $this */
/** @var \customer\entities\Article $model */

use core\helpers\Html;
use customer\widgets\CommentUser;
use mdash\Typograph;
?>
<section class="article">
    <?= Html::h(1, $model->title) ?>
    <article>
        <?= Typograph::fast_apply($model->text, [
            'Text.breakline' => 'off',
        ]) ?>
    </article>
</section>
<?= CommentUser::widget(['item' => $model]) ?>