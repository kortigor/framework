<?php

/** @var \core\web\View $this */

use common\widgets\Alert;
use backend\assets\BackendAsset;
use core\helpers\Url;
use core\helpers\Html;
use core\bootstrap4\NavBar;
use core\bootstrap4\Nav;
use core\bootstrap4\Breadcrumbs;

BackendAsset::register($this);
$this->title = $this->title ?? c('main.siteTitle');
$this->options['breadcrumbs'] = \core\web\Breadcrumbs::getCrumbs();

if (isGuest()) {
	$topMenuItems[] = [
		'label' => '<i class="fas fa-sign-in-alt"></i> Войти',
		'url' => Url::to(['login/'])
	];
} else {
	$topMenuItems[] = [
		'label' => '<i class="fas fa-sign-out-alt"></i> Выйти',
		'url' => Url::to(['logout'])
	];
	$topMenuItems[] = [
		'label' => '<i class="fas fa-user"></i>',
		'url' => Url::to(['user/profile', 'id' => Sys::$app->user->identity->id]),
		'options' => [
			'class' => 'font-weight-bold',
			'title' => Sys::$app->user->identity->fullname . "\n" . Sys::$app->user->identity->phone,
			'data-toggle' => 'tooltip',
			'data-placement' => 'bottom'
		],
	];
}

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Sys::$app->language ?>">

<head>
    <title><?= Html::encode($this->title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=<?= Sys::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Robots" content="index, follow">
    <?= $this->head() ?>
</head>

<body>
    <?= $this->beginBody() ?>
    <noscript>Please turn on JavaScript!</noscript>
    <div class="container-fluid site-width">
        <?php if (isAuth()) : ?>
        <?php NavBar::begin([
				'brandLabel' => 'Панель администратора',
				'brandUrl' => Url::$home,
				'innerContainerOptions' => ['class' => 'container-fluid'],
				// 'options' => ['class' => 'navbar-nav'],
				'options' => ['class' => 'navbar navbar-expand-lg navbar-dark bg-dark'],
			]) ?>
        <?= Nav::widget([
				'items' => $topMenuItems,
				'encodeLabels' => false,
				'options' => ['class' => 'ml-auto navbar-nav'],
			]) ?>
        <?php NavBar::end()	?>
        <?= Breadcrumbs::widget([
				'encodeLabels' => false,
				'homeLink' => \core\web\Breadcrumbs::getHomeCrumb(),
				'links' => $this->options['breadcrumbs'] ?? [],
				'options' => ['class' => 'my-3'],
			]) ?>
        <?= Alert::widget([
				'collection' => $this->options['flashes'] ?? null
			]) ?>
        <?php endif ?>
        <div id="notification-info"></div>
        <?= $this->renderedTemplate ?>
    </div>
    <?= $this->endBody() ?>
</body>

</html>
<?php
$this->endPage();