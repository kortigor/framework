<?php

/** @var \core\web\View $this */
/** @var \Illuminate\Pagination\LengthAwarePaginator $collection */

use core\helpers\Url;
use core\helpers\Html;
use common\widgets\SitePaginator;
use customer\widgets\EntityIndexFilter;
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <?= EntityIndexFilter::widget([
                    'statusProfile' => $statusProfile,
                    'items' => [
                        ['Активные', 'STATUS_ACTIVE', 'active'],
                        ['Заблокированные', 'STATUS_INACTIVE', 'blocked'],
                    ]
                ])
                ?>
                <?= Html::a(
                    '<i class="fas fa-plus-circle"></i> Добавить',
                    Url::to(['user/create']),
                    ['class' => 'btn btn-sm btn-primary']
                )
                ?>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Имя</th>
                            <th>Роль</th>
                            <th>Логин</th>
                            <th>Статус</th>
                        </tr>
                        <?php /** @var \customer\entities\Employee $model */
                        foreach ($collection as $model) : ?>
                            <tr>
                                <td>
                                    <?= Html::a(Html::encode($model->fullname), Url::to(['user/profile', 'id' => $model->id])) ?>
                                </td>
                                <td><?= $model->role ?></td>
                                <td><?= Html::encode($model->username) ?></td>
                                <td><?= $model->status->getHelper()->statusLabel() ?></td>
                            </tr>
                        <?php endforeach ?>
                    </table>
                    <?= SitePaginator::widget(['collection' => $collection, 'containerClass' => 'mt-3']) ?>
                </div>
            </div>
        </div>
    </div>
</div>