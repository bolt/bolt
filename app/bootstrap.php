<?php

namespace Bolt;

use Bolt\Exception\BootException;

/**
 * Second stage loader. Do bootstrapping within a new local scope to avoid
 * polluting the global space.
 *
 * Here we bootstrap the app:
 * - Initialize mb functions for UTF-8
 * - Figure out root path
 * - Bring in the autoloader
 * - Call Bolt\Bootstrap to create the application
 *
 * @throws BootException
 *
 * @return \Silex\Application
 */
return call_user_func(function () {
    // Use UTF-8 for all multi-byte functions
    \mb_internal_encoding('UTF-8');
    \mb_http_output('UTF-8');

    // Resolve Bolt-root
    $boltRootPath = realpath(__DIR__ . '/..');

    // Look for the autoloader in known positions relative to Bolt's root
    $autodetectionMappings = [
        $boltRootPath . '/vendor/autoload.php' => $boltRootPath,
        $boltRootPath . '/../../autoload.php'  => $boltRootPath . '/../../..',
    ];

    $error = true;
    foreach ($autodetectionMappings as $autoloadPath => $rootPath) {
        if (!file_exists($autoloadPath)) {
            continue;
        }

        require_once $autoloadPath;

        $error = false;
        break;
    }

    // None of the mappings matched, error
    if ($error) {
        include $boltRootPath . '/src/Exception/BootException.php';

        BootException::earlyExceptionComposer();
    }

    return Bootstrap::run($rootPath);
});
