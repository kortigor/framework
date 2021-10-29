<?php

/** @var \core\web\View $this */
/** @var Illuminate\Database\Eloquent\Collection $collection */

use common\widgets\SortableTable;
use core\helpers\{
    Url,
    Html
};

SortableTable::widget([
    'url' => '/admin/menu/order/',
    'idFormat' => 'uuid'
]);
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <?= Html::a(
                    '<i class="fas fa-plus-circle"></i> Добавить',
                    Url::to(['menu/createmainmenuitem', 'slug' => Url::getQueryValue('category')]),
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
                            <th>Ссылка</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="sort-items" class="sort-connected">
                        <?php /** @var \customer\entities\Faq $model */
                        foreach ($collection as $ind => $model) : ?>
                            <tr id="sort_<?= $model->id ?>">
                                <td class="sort-handle" title="Переместить / Изменить порядок">
                                    <i class="fas fa-fw fa-arrows-alt"></i>
                                </td>
                                <td><span class="num"><?= $ind + 1 ?></span></td>
                                <td>
                                    <?= Html::a(Html::encode($model->label), Url::to(['menu/edit', 'id' => $model->id])) ?>
                                </td>
                                <td><?= Html::encode($model->url) ?></td>
                                <td class="text-nowrap tightest">
                                    <?= Html::a(
                                        $model->status->getHelper()->blockButtonText(),
                                        Url::to(['menu/block', 'id' => $model->id, 'operation' => $model->status->getHelper()->blockQueryValue()]),
                                        [
                                            'class' => 'btn btn-sm btn-warning get-confirm',
                                            'data-confirm' => $model->status->getHelper()->blockConfirmQuestion(),
                                            'data-method' => 'post',
                                        ]
                                    ) ?>
                                    <?= Html::a(
                                        '<i class="far fa-trash-alt"></i>',
                                        Url::to(['menu/delete', 'id' => $model->id]),
                                        [
                                            'class' => 'btn btn-sm btn-danger get-confirm',
                                            'data-confirm' => "Действительно удалить?\nВосстановить будет невозможно!",
                                            'data-method' => 'post',
                                            'title' => 'Удалить',
                                        ]
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>