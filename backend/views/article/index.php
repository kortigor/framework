<?php

/** @var \core\web\View $this */
/** @var \Illuminate\Pagination\LengthAwarePaginator $collection */

use core\helpers\Url;
use core\helpers\Html;
use customer\ContentUrl;
use common\widgets\SortableTable;
use customer\widgets\EntityIndexFilter;
use customer\widgets\CategoryFilter;
use common\widgets\SitePaginator;

SortableTable::widget([
    'url' => '/admin/article/order/',
    'idFormat' => 'uuid'
]);
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <?= CategoryFilter::widget([
                    'categoryModelClass' => \customer\entities\ArticleCategory::class,
                    'options' => ['class' => 'input-group-sm col-md-3 d-inline-flex align-top pl-0']
                ])
                ?>
                <?= EntityIndexFilter::widget([
                    'statusProfile' => $statusProfile,
                ])
                ?>
                <?= Html::a(
                    '<i class="fas fa-plus-circle"></i> Добавить',
                    Url::to(['article/create', 'slug' => Url::getQueryValue('category')]),
                    ['class' => 'btn btn-sm btn-primary']
                )
                ?>
            </div>
            <div class="card-body">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th class="tightest"></th>
                            <th class="tightest">№</th>
                            <th>Заголовок</th>
                            <th></th>
                            <th>Ссылка</th>
                            <th>Категория</th>
                            <th>Добавлена</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="sort-items" class="sort-connected">
                        <?php /** @var \customer\entities\Article $model */
                        foreach ($collection as $ind => $model) : ?>
                            <tr id="sort_<?= $model->id ?>">
                                <td class="sort-handle" title="Переместить / Изменить порядок">
                                    <i class="fas fa-fw fa-arrows-alt"></i>
                                </td>
                                <td><span class="num"><?= $ind + 1 ?></span></td>
                                <td>
                                    <?= Html::a(Html::encode($model->title), Url::to(['article/edit', 'id' => $model->id])) ?>
                                </td>
                                <td>
                                    <?= ($model->isCommentsAllowed()
                                        ? '<i class="fas fa-comment" title="Комментарии разрешены" data-toggle="tooltip"></i>'
                                        : '<i class="fas fa-comment-slash" title="Комментарии запрещены" data-toggle="tooltip"></i>') ?>
                                </td>
                                <td>
                                    <?= Html::a(
                                        '<i class="fas fa-fw fa-external-link-alt"></i>',
                                        ContentUrl::to($model),
                                        ['class' => 'mr-3', 'target' => '_blank', 'title' => 'Открыть в новой вкладке', 'data-toggle' => 'tooltip']
                                    ) ?>
                                    <span class='copy-to-clipboard small'><?= ContentUrl::to($model) ?></span>
                                </td>
                                <td class="small"><?= Html::encode($model->category->name) ?></td>
                                <td class="text-nowrap small"><?= $model->created_at->format('d.m.Y H:i:s') ?></td>
                                <td class="text-nowrap">
                                    <?= Html::a(
                                        $model->status->getHelper()->blockButtonText(),
                                        Url::to(['article/block', 'id' => $model->id, 'operation' => $model->status->getHelper()->blockQueryValue()]),
                                        [
                                            'class' => 'btn btn-sm btn-warning get-confirm',
                                            'data-confirm' => $model->status->getHelper()->blockConfirmQuestion(),
                                            'data-method' => 'post',
                                        ]
                                    ) ?>
                                    <?= Html::a(
                                        '<i class="far fa-trash-alt"></i> Удалить',
                                        Url::to(['article/delete', 'id' => $model->id]),
                                        [
                                            'class' => 'btn btn-sm btn-danger get-confirm',
                                            'data-confirm' => "Действительно удалить?\nВосстановить будет невозможно!",
                                            'data-method' => 'post',
                                        ]
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
                <?= SitePaginator::widget(['collection' => $collection, 'containerClass' => 'mt-3']) ?>
            </div>
        </div>
    </div>
</div>