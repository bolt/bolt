<?php

/*
 * This is global bootstrap for autoloading
 */

// Suite clean up extension
include_once __DIR__ . '/extensions/CleanupExtension.php';

// Define our install type
if (file_exists(__DIR__ . '/../../../../../vendor/bolt/bolt/')) {
    $installType = 'composer';
} else {
    $installType = 'git';
}

// Create a constant that defines the Codeception location
if (!defined('CODECEPTION_ROOT')) {
    define('CODECEPTION_ROOT', realpath(__DIR__));
}

// Create a constant that defines the root location
if (!defined('PROJECT_ROOT')) {
    if ($installType === 'composer') {
        define('PROJECT_ROOT', realpath(CODECEPTION_ROOT . '/../../../../..'));
    } elseif ($installType === 'git') {
        define('PROJECT_ROOT', realpath(CODECEPTION_ROOT . '/../..'));
    }
}

// Path to Nut
if (!defined('NUT_PATH')) {
    if ($installType === 'composer') {
        define('NUT_PATH', realpath(PROJECT_ROOT . '/vendor/bolt/bolt/app/nut'));
    } elseif ($installType === 'git') {
        define('NUT_PATH', realpath(PROJECT_ROOT . '/app/nut'));
    }
}

echo "Bootstrapped with:\n";
echo "    Install type: $installType", "\n";
echo '    Project root: ' . PROJECT_ROOT, "\n";
echo '    Codeception root: ' . CODECEPTION_ROOT, "\n";
echo '    Nut path: ' . NUT_PATH, "\n";
