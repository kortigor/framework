<?php

/** @var \core\web\View $this */
/** @var \Illuminate\Pagination\LengthAwarePaginator $collection */
/** @var \customer\entities\Article $model */

use customer\ContentUrl;
use common\widgets\SitePaginator;
use core\helpers\Html;
?>
<ul class="list-unstyled article-list">
    <?php foreach ($collection as $model) : ?>
        <li>
            <?= Html::hLink(3, $model->title, ContentUrl::to($model)) ?>
            <div class="announce"><?= $model->announce ?></div>
            <div class="date">
                <i class="far fa-calendar-alt"></i>
                <?= f()->asDate($model->created_at, 'php:j F Y') ?>
            </div>
        </li>
    <?php endforeach ?>
</ul>
<?= SitePaginator::widget([
    'collection' => $collection,
    'containerClass' => 'my-4',
    'itemsPerPage' => [15, 30, 60],
    'signBeforePageSizer' => t('На странице')
]) ?>