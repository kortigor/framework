<?php

/** @var \core\web\View $this */
/** @var \Illuminate\Pagination\LengthAwarePaginator $collection */

use core\helpers\Url;
use core\helpers\Html;
use common\widgets\SortableTable;
use customer\widgets\EntityIndexFilter;
use common\widgets\SitePaginator;

SortableTable::widget([
    'url' => '/admin/articlecat/order/',
    'idFormat' => 'uuid'
]);
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <?= EntityIndexFilter::widget([
                    'statusProfile' => $statusProfile,
                ])
                ?>
                <?= Html::a(
                    '<i class="fas fa-plus-circle"></i> Добавить',
                    Url::to(['articlecat/create']),
                    ['class' => 'btn btn-sm btn-primary']
                )
                ?>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>№</th>
                            <th>Название</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="sort-items" class="sort-connected">
                        <?php /** @var \customer\entities\ArticleCategory $model */
                        foreach ($collection as $ind => $model) : ?>
                            <tr id="sort_<?= $model->id ?>">
                                <td class="tightest sort-handle" title="Переместить / Изменить порядок">
                                    <i class="fas fa-fw fa-arrows-alt"></i>
                                </td>
                                <td class='tightest'><span class="num"><?= $ind + 1 ?></span></td>
                                <td>
                                    <?= Html::a(Html::encode($model->name), Url::to(['articlecat/edit', 'id' => $model->id])) ?>
                                </td>
                                <td class='tightest'>
                                    <?= Html::a(
                                        $model->status->getHelper()->blockButtonText(),
                                        Url::to(['articlecat/block', 'id' => $model->id, 'operation' => $model->status->getHelper()->blockQueryValue()]),
                                        [
                                            'class' => 'btn btn-sm btn-warning get-confirm',
                                            'data-confirm' => $model->status->getHelper()->blockConfirmQuestion(),
                                            'data-method' => 'post',
                                        ]
                                    ) ?>
                                    <?= Html::a(
                                        '<i class="far fa-trash-alt"></i> Удалить',
                                        Url::to(['articlecat/delete', 'id' => $model->id]),
                                        [
                                            'class' => 'btn btn-sm btn-danger get-confirm',
                                            'data-confirm' => "C категорией будет удален её контент.\nВосстановить будет невозможно!\nДействительно удалить?",
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