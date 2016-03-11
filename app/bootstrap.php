<?php

namespace Bolt;

use Bolt\Exception\LowlevelException;

/**
 * Second stage loader. Do bootstrapping within a new local scope to avoid
 * polluting the global space.
 *
 * Here we bootstrap the app:
 * - Initialize mb functions for UTF-8
 * - Figure out path structure
 * - Bring in the autoloader
 * - Load and verify configuration
 * - Initialize the application
 *
 * @param array $paths Paths in the format ['name' => 'relative/path']
 *
 * @throws LowlevelException
 *
 * @return \Closure
 */
return function (array $paths = []) {
    // Use UTF-8 for all multi-byte functions
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');

    // Resolve Bolt-root
    $boltRootPath = realpath(__DIR__ . '/..');

    // Look for the autoloader in known positions relative to the Bolt-root,
    // and autodetect an appropriate configuration class based on this
    // information. (autoload.php path maps to a configuration class)
    $autodetectionMappings = [
        $boltRootPath . '/vendor/autoload.php' => 'Standard',
        $boltRootPath . '/../../autoload.php'  => 'Composer',
    ];

    foreach ($autodetectionMappings as $autoloadPath => $configType) {
        if (file_exists($autoloadPath)) {
            $loader = require $autoloadPath;
            // Instantiate the configuration class
            $configClass = '\\Bolt\\Configuration\\' . $configType;
            /** @var \Bolt\Configuration\ResourceManager $config */
            $config = new $configClass($loader);
            break;
        }
    }

    // None of the mappings matched, error
    if (!isset($config)) {
        include $boltRootPath . '/src/Exception/LowlevelException.php';
        throw new LowlevelException(
            'Configuration autodetection failed because The file ' .
            "<code>vendor/autoload.php</code> doesn't exist. Make sure " .
            "you've installed the required components with Composer."
        );
    }

    // Set any non-standard paths
    foreach ($paths as $name => $path) {
        $config->setPath($name, $path);
    }

    /** @var \Bolt\Configuration\ResourceManager $config */
    $config->verify();

    // Create the 'Bolt application'
    $app = new Application(['resources' => $config]);

    // Initialize the 'Bolt application': Set up all routes, providers, database, templating, etc..
    $app->initialize();

    return $app;
};
