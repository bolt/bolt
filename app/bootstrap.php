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
    // Resolve Bolt-root
    $boltRootPath = realpath(__DIR__ . '/..');

    // Look for the autoloader in known positions relative to Bolt's root
    $autodetectionMappings = [
        $boltRootPath . '/vendor/autoload.php' => $boltRootPath,
        $boltRootPath . '/../../autoload.php'  => $boltRootPath . '/../../..',
    ];

    foreach ($autodetectionMappings as $autoloadPath => $rootPath) {
        if (!file_exists($autoloadPath)) {
            continue;
        }

        require_once $autoloadPath;

        return Bootstrap::run($rootPath);
    }

    require_once $boltRootPath . '/src/Exception/BootException.php';
    throw BootException::earlyExceptionComposer();
});
