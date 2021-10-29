<?php

use core\helpers\Url;
use core\helpers\Inflector;

?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>Присланное</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?= $orders ?></h3>
                                <p>
                                    <?= Inflector::numberPlural($orders, ['Новый заказ', 'Новых заказа', 'Новых заказов']) ?>
                                </p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <a href="<?= Url::to(['order/']) ?>" class="small-box-footer">
                                Подробнее <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?= $comments ?></h3>
                                <p>
                                    <?= Inflector::numberPlural($comments, ['комментарий', 'комментария', 'комментариев']) ?>
                                </p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <a href="<?= Url::to(['comments/']) ?>" class="small-box-footer">
                                Подробнее <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?= $faq ?></h3>
                                <p>
                                    <?= Inflector::numberPlural($faq, ['вопрос', 'вопроса', 'вопросов']) ?>
                                </p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-question"></i>
                            </div>
                            <a href="<?= Url::to(['faq/new']) ?>" class="small-box-footer">
                                Подробнее <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info">
            <h5><i class="icon fas fa-info"></i> Информация об аккаунтах</h5>
            <ul>
                <li>Авторизация одной учётной записи с разных компьютеров приводит к завершению более ранней сессии
                    авторизации.<br>
                    Например, Иванов зашел с рабочего места в панель администратора под учётной записью
                    <b>admin</b>.
                    Затем Петров зашел с другого рабочего места под той же учётной записью.
                    Это приведет к утрате авторизации Иванова.
                </li>
                <li>Такое поведение реализовано в целях безопасности.</li>
                <li>Для одновременного доступа разных людей к панели администратора, для каждого надо
                    <a href="<?= Url::to(['user/']) ?>">создать учётную запись</a>.
                </li>
                <li>Если доставляет неудобства, можно отключить обратившись к разработчику.</li>
            </ul>
        </div>
    </div>
</div>