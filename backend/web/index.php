<?php

defined('APP') or define('APP', 'backend');

if (ob_get_level()) {
    ob_end_clean();
    ob_start();
}

require_once __DIR__ . '/../../common/config/boot.php';
require_once __DIR__ . '/../config/boot.php';

$config = core\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../config/main.php',
    require __DIR__ . '/../config/main-local.php'
);

(new \core\web\Application($config))->run();