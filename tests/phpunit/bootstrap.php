<?php

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../../../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../../../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../../../../vendor/autoload.php';
} else {
    exit('Could not find the vendor autoloader');
}

// Install base location
if (!defined('TEST_ROOT')) {
    define('TEST_ROOT', dirname(dirname(__DIR__)));
}

// PHPUnit's base location
if (!defined('PHPUNIT_ROOT')) {
    define('PHPUNIT_ROOT', realpath(TEST_ROOT . '/tests/phpunit/unit'));
}

// PHPUnit's temporary web root… It doesn't exist yet, so we can't realpath()
if (!defined('PHPUNIT_WEBROOT')) {
    define('PHPUNIT_WEBROOT', dirname(PHPUNIT_ROOT) . '/web-root');
}
