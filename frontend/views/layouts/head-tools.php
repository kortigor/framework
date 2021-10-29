<?php

/** @var \core\web\View $this */
/** @var \core\web\Cart $cartItems */
/** @var \core\web\CompareItems $compareItems */

use core\helpers\Html;
use core\helpers\Url;
use core\bootstrap4\NavBar;
use core\bootstrap4\Nav;
use common\widgets\SearchSite;

NavBar::begin([
    'brandImage' => '/img/' . t('logo_rus.png'),
    'brandOptions' => [
        'class' => 'd-lg-none',
    ],
    'options' => [
        'class' => 'head-tools navbar-expand-lg navbar-light bg-light',
    ],
    'collapseOptions' => ['class' => 'justify-content-center'],
    'innerContainerOptions' => ['class' => 'container-fluid'],
])
?>
<div class="head-button-wrapper">
    <?= Html::button(
        '<i class="fas fa-fw fa-question-circle"></i> ' . t('Задать вопрос'),
        [
            'class' => 'btn btn-light my-sm-1 my-lg-0 ml-lg-3',
            'id' => 'ask-question-button',
            'name' => 'ask-question-button',
        ]
    ) ?>
</div>
<div class="head-search-wrapper my-sm-1 my-lg-0 mr-lg-3 ml-lg-1 mr-sm-2">
    <?= SearchSite::widget([
        'action' => Url::to(['search/']),
        'suggestUrl' => 'suggest/sitemain',
        'placeholder' => t('Поиск по сайту'),
        'minLength' => c('main.search.minLengthToSuggest')
    ]) ?>
</div>
<?= Nav::widget([
    'items' => [
        [
            'label' => Html::tag('span', $compareItems->count() ?: '', ['id' => 'compare-items-number'])
                . '<i class="fas fa-fw fa-balance-scale-right"></i> ' . t('Сравнение'),
            'url' => ['compare/'],
            'options' => ['id' => 'compare-top-item'],
        ],
        [
            'label' => Html::tag('span', $cartItems->count() ?: '', ['id' => 'cart-items-number'])
                . '<i class="fas fa-fw fa-shopping-cart"></i> ' . t('Корзина'),
            'url' => ['cart/'],
            'options' => ['id' => 'cart-top-item']
        ],
    ],
    'options' => ['class' => 'navbar-nav', 'id' => 'headNav'],
    'encodeLabels' => false
]) ?>

<?php NavBar::end();