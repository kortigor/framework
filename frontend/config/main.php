<?php

declare(strict_types=1);

$appPath = realpath(__DIR__ . '/../');

return [
    'siteTitle' => 'Title',
    'appId' => 'frontend',
    'appPath' => normalizePath($appPath),
    'controllerNamespace' => 'frontend\controllers',
    'view' => [
        'path' => normalizePath($appPath . '/views'),
        'layoutsPath' => normalizePath($appPath . '/views/layouts'),
    ],

    'container' => [
        'singletons' => [
            \core\routing\Router::class => [
                '@p' => [
                    'rules' => [
                        // Custom routing rules
                        ['Article', '/article/{slug:slug}', 'article/article'],
                        ['ArticleCat', '/article-cat/{slug:slug}', 'article/category'],
                    ]
                ]
            ],
        ],
    ],

    'siteBlocks' => [
        'sideLeft' => [
            \frontend\panels\LibraryMenu::class => [
                'on' => ['Article'],
                'title' => fn () => t('Библиотека'),
            ],
        ],

        'sideRight' => [],

        'centerBottom' => [],

        'footerBottom' => [],
    ],
];