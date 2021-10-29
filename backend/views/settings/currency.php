<?php

/** @var \core\web\View $this */
/** @var \customer\models\forms\SettingsForm $model */
/** @var \customer\entities\Setting $set */

use customer\entities\Currency;
use customer\entities\CurrencySign;
use core\bootstrap4\ActiveForm;
use core\bootstrap4\Template;
use core\helpers\Html;
use core\helpers\Url;

/** @var Currency $usd */
$usd = $currency->firstWhere('code', 'USD');
/** @var Currency $eur */
$eur = $currency->firstWhere('code', 'EUR');
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php $form = ActiveForm::begin(['options' => ['id' => 'currencyForm']]) ?>
                <div class="row">
                    <div class="col-sm-8">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Проценты за конвертацию валют</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($model->collection() as $set) : ?>
                                        <div class="col-sm-6">
                                            <?= $set->field($form, $model, ['template' => Template::inputGroupAppendText('%')]) ?>
                                        </div>
                                    <?php endforeach ?>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <?= Html::submitButton('<i class="far fa-fw fa-save"></i> Сохранить', [
                                            'class' => 'btn btn-primary',
                                            'name' => 'save-button',
                                        ]) ?>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                </div>
                <?php ActiveForm::end() ?>
                <div class="row">
                    <div class="col-sm-4">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">USD &rarr; RUB</h3>
                                <div class="card-tools">
                                    <small>Обновлен: <?= $usd->updated_at->format('d.m.Y H:i.s') ?></small>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                ЦБ РФ <?= (float) $usd->rate ?> + <?= c('site.rub_usd_percent') ?>% =
                                <?= c('currency.USD.RUB') ?> <?= CurrencySign::RUB ?>
                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">EUR &rarr; RUB</h3>
                                <div class="card-tools">
                                    <small>Обновлен: <?= $eur->updated_at->format('d.m.Y H:i.s') ?></small>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                ЦБ РФ <?= (float) $eur->rate ?> + <?= c('site.rub_eur_percent') ?>% =
                                <?= c('currency.EUR.RUB') ?> <?= CurrencySign::RUB ?>
                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-4">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">USD &rarr; EUR</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <?= c('currency.USD.EUR') ?> <?= CurrencySign::EUR ?>
                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">EUR &rarr; USD</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <?= c('currency.EUR.USD') ?> <?= CurrencySign::USD ?>
                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-4">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">RUB &rarr; USD</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <?= c('currency.RUB.USD') ?> <?= CurrencySign::USD ?>
                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">RUB &rarr; EUR</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <?= c('currency.RUB.EUR') ?> <?= CurrencySign::EUR ?>
                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <div class="info-box shadow">
                            <span class="info-box-icon bg-warning"><i class="fas fa-external-link-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Ссылка для обновления курсов валют</span>
                                <span class="info-box-number">
                                    <?= Html::a(
                                        'Проверить и обновить',
                                        Url::to(['currency/cronupdate', '@home' => ''], true),
                                        ['target' => '_blank']
                                    ) ?>
                                </span>
                            </div>
                            <!-- /.info-box-content -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>