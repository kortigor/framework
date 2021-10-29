<?php
// In CLI mode $_SERVER['DOCUMENT_ROOT'] is directory where script is located.
// Change it to real site root.
$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../';

// In CLI mode $_SERVER['REMOTE_ADDR'] is not exists.
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

define('SYS_DEBUG', true);
define('SYS_ENV', 'dev');
defined('APP') or define('APP', 'console');

require_once __DIR__ . '/../common/config/boot.php';

$config = core\helpers\ArrayHelper::merge(
    require __DIR__ . '/../common/config/main.php',
    require __DIR__ . '/../common/config/main-local.php',
);

(new \console\ApplicationMigration($config))->run();