<?php

/** @var \core\web\View $this */

use core\helpers\Url;
use core\helpers\Html;
use core\bootstrap4\NavBar;
use core\bootstrap4\Nav;
use core\bootstrap4\Breadcrumbs as BreadcrumbsDisplay;
use core\web\Breadcrumbs;
use common\widgets\Alert;
use backend\assets\BackendAsset;
use backend\widgets\MenuAdminLTE;
use backend\controllers\SuggestController;
use customer\AdminMenuConfig;
use customer\widgets\SearchSite;

BackendAsset::register($this);

$this->blocks['breadcrumbs'] = Breadcrumbs::getCrumbs();
$this->blocks['nav-side-left'] = (new AdminMenuConfig)->toArray();
$this->blocks['nav-top-left'][] = [
	'label' => '<i class="fas fa-bars"></i>',
	'options' => [
		'data-widget' => 'pushmenu',
		'data-enable-remember' => 'true',
		'data-no-transition-after-reload' => 'false'
	],
];
$this->blocks['nav-top-right'] = [
	[
		'label' => '<i class="fas fa-sign-out-alt"></i> Выйти',
		'url' => Url::to(['logout'])
	],
	[
		'label' => '<i class="fas fa-user"></i>',
		'url' => Url::to(['user/profile', 'id' => Sys::$app->user->identity->id]),
		'options' => [
			'class' => 'font-weight-bold',
			'title' => Html::encode(Sys::$app->user->identity->fullname),
			'data-toggle' => 'tooltip',
			'data-placement' => 'bottom'
		],
	]
];

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <title><?= Html::encode($this->title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=<?= Sys::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Robots" content="index, follow">
    <?= $this->head() ?>
</head>

<body class="hold-transition sidebar-mini">
    <?= $this->beginBody() ?>
    <noscript>Please turn on JavaScript!</noscript>
    <div class="wrapper">
        <?php NavBar::begin([
			'innerContainerOptions' => ['class' => 'container-fluid'],
			'options' => ['class' => 'main-header navbar navbar-expand navbar-white navbar-light'],
		]) ?>
        <?= Nav::widget([
			'items' => $this->blocks['nav-top-left'],
			'encodeLabels' => false,
			'options' => ['class' => 'navbar-nav'],
		]) ?>
        <div class="mx-3 flex-fill">
            <?= SearchSite::widget([
				'suggesterController' => SuggestController::class,
				'minLength' => c('main.search.minLengthToSuggest')
			]) ?>
        </div>
        <?= Nav::widget([
			'items' => $this->blocks['nav-top-right'],
			'encodeLabels' => false,
			'options' => ['class' => 'ml-auto navbar-nav'],
		]) ?>
        <?php NavBar::end()	?>
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <?= Html::a(
				Html::img('/admin/img/AdminLogo.png', ['class' => 'brand-image img-circle elevation-3', 'alt' => 'APP'])
					. Html::tag('span', 'Админпанель', ['class' => 'brand-text font-weight-light']),
				Url::$home,
				['class' => 'brand-link']
			) ?>
            <div class="sidebar">
                <nav class="mt-2">
                    <?= MenuAdminLTE::widget([
						'options' => [
							'class' => 'nav nav-pills nav-sidebar flex-column',
							'data-widget' => 'treeview'
						],
						'items' => $this->blocks['nav-side-left'],
					]) ?>
                </nav>
            </div>
        </aside>
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-12">
                            <?= BreadcrumbsDisplay::widget([
								'encodeLabels' => false,
								'homeLink' => Breadcrumbs::getHomeCrumb(),
								'links' => $this->blocks['breadcrumbs'] ?? [],
							]) ?>
                        </div>
                    </div>
                </div>
            </section>
            <section class="content">
                <?= Alert::widget([
					'collection' => $this->blocks['flashes']
				]) ?>
                <div id="notification-info"></div>
                <?= $this->renderedTemplate ?>
            </section>
        </div>
        <footer class="main-footer">
            <strong>&copy; Company Name.</strong> Development by <a href="mailto:kort.igor@gmail.com">Kort</a>, 2021
        </footer>
    </div>
    <?= $this->endBody() ?>
</body>

</html>
<?php
$this->endPage();