<?php

namespace Bolt;

use Bolt\Configuration\Composer;
use Bolt\Configuration\Standard;
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

    // Look for the autoloader in known positions relative to the Bolt-root,
    // and autodetect an appropriate configuration class based on this
    // information. (autoload.php path maps to a configuration class)
    $autodetectionMappings = [
        $boltRootPath . '/vendor/autoload.php' => [Standard::class, $boltRootPath],
        $boltRootPath . '/../../autoload.php'  => [Composer::class, $boltRootPath . '/../../..'],
    ];

    foreach ($autodetectionMappings as $autoloadPath => list($resourcesClass, $rootPath)) {
        if (!file_exists($autoloadPath)) {
            continue;
        }

        require_once $autoloadPath;

        /** @deprecated Can be removed when support for PHP 5.5 is dropped. */
        // Use UTF-8 for all multi-byte functions
        \mb_internal_encoding('UTF-8');
        \mb_http_output('UTF-8');

        return Bootstrap::run($rootPath, $resourcesClass);
    }

    require_once $boltRootPath . '/src/Exception/BootException.php';
    throw BootException::earlyExceptionComposer();
});
