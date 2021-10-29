<?php

/** @var \core\web\View $this */
/** @var Illuminate\Database\Eloquent\Collection $notes */
/** @var Illuminate\Database\Eloquent\Collection $news */

use customer\ContentUrl;
use core\helpers\Html;
use core\helpers\Url;
?>
<div class="row mb-3">
    <div class="col-md-6 container-notes border-right pr-md-4">
        <div class="main-container-head"><?= t('Обратите внимание') ?></div>
    </div>
    <div class="col-md-6 container-news pl-md-4">
        <div class="main-container-head"><?= Html::a(t('Новости'), Url::to(['news/'])) ?></div>
    </div>
</div>