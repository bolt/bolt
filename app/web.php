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

    // Fix server variables for PHP built-in server so base path is correctly determined.
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $frame = end($trace);
    // Absolute path to entry file
    $_SERVER['SCRIPT_FILENAME'] = $frame['file'];
    // Relative path to entry file from document root (dir the server is point to)
    $_SERVER['SCRIPT_NAME'] = preg_replace('#^' . preg_quote($_SERVER['DOCUMENT_ROOT'], '\\') . "#", '', $_SERVER['SCRIPT_FILENAME']);
}

return require __DIR__ . '/bootstrap.php';
