<?php
/*
 * This could be loaded on a very old version of PHP so no syntax/methods over 5.2 in this file.
 */
use Bolt\Exception\BootException;

if (version_compare(PHP_VERSION, '5.5.9', '<')) {
    require dirname(__DIR__) . '/src/Exception/BootException.php';

    BootException::earlyExceptionVersion();
}

if (PHP_SAPI === 'cli-server') {
    if (is_file($_SERVER['DOCUMENT_ROOT'] . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']))) {
        return false;
    }
}

return require __DIR__ . '/bootstrap.php';
