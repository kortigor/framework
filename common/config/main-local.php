<?php

return [
    'reCaptcha' => [
        'siteKey' => '',
        'secretKey' => ''
    ],

    'container' => [
        'singletons' => [
            \core\orm\Config::class => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'customer',
                'username' => 'root',
                'password' => '',
            ],
            'confMail' => [
                '@p' => [
                    'host' => 'smtp.googlemail.com',
                    'port' => 465,
                    'encryption' => 'ssl',
                    'login' => '',
                    'password' => '',
                ]
            ],
        ],
    ]
];