<?php

/** @var \core\web\View $this */
/** @var \core\web\BlocksManager $blockManager */

use frontend\assets\FrontendAsset;
use core\bootstrap4\Breadcrumbs as BreadcrumbsDisplay;
use core\web\Breadcrumbs;
use core\helpers\Html;
use common\widgets\Alert;
use common\widgets\LangSwitch;
use customer\widgets\SiteMenu;

FrontendAsset::register($this);

$this->blocks['breadcrumbs'] = Breadcrumbs::getCrumbs();
$this->blocks['sideLeft'] = $blockManager->sideLeft();
$this->blocks['sideRight'] = $blockManager->sideRight();
$this->blocks['centerBottom'] = $blockManager->centerBottom();
$this->blocks['footerBottom'] = $blockManager->footerBottom();

// Side display options
$gridOptions = ['class' => ['main-grid']];
if ($this->block('sideLeft') && $this->block('sideRight')) {
    Html::addCssClass($gridOptions, 'side-both');
} elseif (!$this->block('sideLeft') && !$this->block('sideRight')) {
    Html::addCssClass($gridOptions, 'side-none');
} elseif ($this->block('sideLeft') && !$this->block('sideRight')) {
    Html::addCssClass($gridOptions, 'side-left');
} elseif (!$this->block('sideLeft') && $this->block('sideRight')) {
    Html::addCssClass($gridOptions, 'side-right');
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
    <?= Html::beginTag('div', $gridOptions) ?>
    <!-- Header -->
    <header id="siteHeader">
        <div class="head-info">
            <?= Html::a(Html::img('/img/' . t('logo_rus.png')), '/') ?>
            <address>
                <ul class="fa-ul text-nowrap">
                    <li class="mb-2">
                        <span class="fa-li"><i class="fas fa-phone"></i></span>
                        <a href="tel:+73833087500">+7 (383) 308-75-00 &ndash; многоканальный</a>
                    </li>
                    <li>
                        <span class="fa-li"></span>
                        <a href="tel:88003506708">8-800-3-506-708 &ndash; из России бесплатно</a>
                    </li>
                </ul>
            </address>
        </div>
    </header>
    <!-- /Header -->
    <!-- Site menu -->
    <section id="siteMenu">
        <?= $this->renderPart('/frontend/views/layouts/head-tools') ?>
        <div class="ruler ruler-high"></div>
    </section>
    <!-- /Site menu -->
    <!-- Content -->
    <main id="siteContent">
        <div class="container-fluid">
            <div id="notification-info"></div>
            <?= BreadcrumbsDisplay::widget([
                'encodeLabels' => false,
                'homeLink' => Breadcrumbs::getHomeCrumb(),
                'links' => $this->blocks['breadcrumbs'] ?? [],
                'options' => ['class' => 'site-crumb'],
            ]) ?>
            <?= Alert::widget([
                'collection' => $this->blocks['flashes']
            ]) ?>
            <?= $this->renderedTemplate ?>
            <section id='siteCenterBottom'>
                <?= $this->block('centerBottom') ?>
            </section>
        </div>
    </main>
    <!-- /Content -->
    <?php if ($this->block('sideLeft')) : ?>
    <!-- Left -->
    <aside id="sideLeft">
        <div class="side-border-left">
            <?= $this->block('sideLeft') ?>
        </div>
    </aside>
    <!-- /Left -->
    <?php endif ?>
    <?php if ($this->block('sideRight')) : ?>
    <!-- Right -->
    <aside id="sideRight">
        <div class="side-border-right">
            <?= $this->block('sideRight') ?>
        </div>
    </aside>
    <!-- /Right -->
    <?php endif ?>
    <!-- Footer -->
    <footer id="siteFooter">
        <div class="ruler"></div>
        <div class="foot-content">
            <section id="footerStatic">
                <div class="foot-logo">
                    <?= Html::img('/img/' . t('footer_logo_rus.gif')) ?>
                </div>
                <div class="lang-switch">
                    <?= LangSwitch::widget([
                        'currentLang' => Sys::$app->language,
                        'languages' => c('main.language.supported'),
                        'options' => ['encode' => false, 'class' => 'vdiv d-inline-flex'],
                        'textOptions' => ['class' => 'badge badge-primary'],
                    ])
                    ?>
                </div>
                <div class="site-copyrights">Компания &copy; 2000 &mdash; <?= date('Y') ?></div>
            </section>
            <section id="footerPanels">
                <?= $this->block('footerBottom') ?>
            </section>
        </div>
    </footer>
    <!-- /Footer -->
    <?= Html::endTag('div') ?>
    <?= $this->endBody() ?>
    <?= $this->renderPart('/frontend/views/layouts/footer-metrics') ?>

</body>

</html>
<?php
$this->endPage();