<?php

/** @var \core\web\View $this */

use common\assets\{
    CoreAsset,
    FontawesomeAsset
};

CoreAsset::register($this);
FontawesomeAsset::register($this);
$this->title = $this->title ?? c('main.siteTitle');
$this->options['breadcrumbs'] = \core\web\Breadcrumbs::getCrumbs();
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <title><?= $this->title ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Robots" content="index, follow">
    <?= $this->head() ?>
</head>

<body>
    <?= $this->beginBody() ?>
    <noscript>Please turn on JavaScript!</noscript>
    <div class="container-fluid site-width">
        <div id="notification-info"></div>
        <?= $this->renderedTemplate ?>
    </div>
    <?= $this->endBody() ?>
</body>

</html>
<?php
$this->endPage();