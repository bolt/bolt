<?php
/*
 * This could be loaded on a very old version of PHP so no syntax/methods over 5.2 in this file.
 */

if (version_compare(PHP_VERSION, '5.5.9', '<')) {
    require dirname(__FILE__) . '/legacy.php';
    exit(1);
}

if (PHP_SAPI === 'cli-server') {
    if (is_file($_SERVER['DOCUMENT_ROOT'] . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']))) {
        return false;
    }
}

return require dirname(__FILE__) . '/bootstrap.php';
