<?php

if ($_SERVER['REMOTE_ADDR'] === '127.0.0.1') {
    defined('SYS_DEBUG') or define('SYS_DEBUG', true);
    defined('SYS_ENV') or define('SYS_ENV', 'dev');
} else {
    defined('SYS_DEBUG') or define('SYS_DEBUG', false);
    defined('SYS_ENV') or define('SYS_ENV', 'prod');
}

/** @var string DS same as `DIRECTORY_SEPARATOR` built in constant */
define('DS', DIRECTORY_SEPARATOR);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../core/Sys.php';
Sys::autoload(__DIR__ . '/../../');
require_once __DIR__ . '/../../core/functions.php';

/**
 * @var float Timestamp of current time moment.
 * Used to measure of script execution time like:
 * ```
 * $endTime = getMicrotime();
 * $executionTime = $endTime - TIME_START;
 * echo $executionTime;
 * ```
 */
define('TIME_START', getMicrotime());

/**
 * @var string DATA_ROOT_HTML Begin of html path of site data files.
 * Used to create absolute html paths to data files.
 * 
 * Example:
 * ```
 * echo '<img srs="'.DATA_ROOT_HTML.'user_avatars/murzik.jpg">';
 * // <img srs="/data/user_avatars/murzik.jpg">
 * ```
 */
define('DATA_ROOT_HTML', '/data/');

/**
 * @var string DATA_ROOT_PHP Root directory where site data stored in.
 * Used in PHP's files operations.
 */
define('DATA_ROOT_PHP', fsPath('/data'));