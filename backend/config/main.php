<?php

declare(strict_types=1);

$appPath = realpath(__DIR__ . '/../');

return [
    'appId' => 'backend',
    'siteTitle' => 'Панель администратора - Компания',
    'appPath' => normalizePath($appPath),
    'homeUrl' => '/admin/',
    'loginURL' => '/admin/login/',
    'controllerNamespace' => 'backend\controllers',

    'view' => [
        'path' => normalizePath($appPath . '/views'),
        'layoutsPath' => normalizePath($appPath . '/views/layouts'),
    ],

    'galleryUploadToken' => 'RESUMABLE-FILES-UPLOAD-TOKEN-O525z!',

    'container' => [
        'singletons' => [
            \core\routing\Router::class => [
                '@p' => [
                    'rules' => [
                        // Custom routing rules
                        ['logout', '/logout', 'login/logout'],
                    ]
                ]
            ],
        ],
    ],
];