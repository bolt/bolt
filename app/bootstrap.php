<?php

namespace Bolt;

use Bolt\Configuration\Composer;
use Bolt\Configuration\Standard;
use Bolt\Exception\LowlevelException;
use Symfony\Component\Yaml\Yaml;

/**
 * Second stage loader. Do bootstrapping within a new local scope to avoid
 * polluting the global space.
 *
 * Here we bootstrap the app:
 * - Initialize mb functions for UTF-8
 * - Figure out root path
 * - Bring in the autoloader
 * - Load initialization config from .bolt file
 * - Load and verify configuration
 * - Initialize the application
 *
 * @throws LowlevelException
 *
 * @return \Silex\Application
 */
return call_user_func(function () {
    // Use UTF-8 for all multi-byte functions
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');

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
        // Instantiate the configuration class
        /** @var \Bolt\Configuration\ResourceManager $resources */
        $resources = new $resourcesClass($rootPath);
        break;
    }

    // None of the mappings matched, error
    if (!isset($resources)) {
        include $boltRootPath . '/src/Exception/LowlevelException.php';
        throw new LowlevelException(
            'Configuration autodetection failed because The file ' .
            "<code>vendor/autoload.php</code> doesn't exist. Make sure " .
            "you've installed the required components with Composer."
        );
    }

    /*
     * Load initialization config needed to bootstrap application.
     *
     * In order for paths to be customized and still have the standard
     * index.php (web) and nut (CLI) work, there needs to be a standard
     * place these are defined. This is ".bolt.yml" or ".bolt.php" in the
     * project root (determined above).
     *
     * Yes, YAML and PHP are supported here (not both). YAML works for
     * simple values and PHP supports any programmatic logic if required.
     */
    $config = [
        'paths' => [],
    ];

    if (file_exists($rootPath . '/.bolt.yml')) {
        $yaml = Yaml::parse(file_get_contents($rootPath . '/.bolt.yml')) ?: [];
        $config = array_replace_recursive($config, $yaml);
    } elseif (file_exists($rootPath . '/.bolt.php')) {
        $php = include $rootPath . '/.bolt.php';
        if (is_array($php)) {
            $config = array_replace_recursive($config, $php);
        }
    }

    // Set any non-standard paths
    foreach ((array) $config['paths'] as $name => $path) {
        $resources->setPath($name, $path);
    }

    /** @var \Bolt\Configuration\ResourceManager $config */
    $resources->verify();

    // Create the 'Bolt application'
    $app = new Application(['resources' => $resources]);

    // Initialize the 'Bolt application': Set up all routes, providers, database, templating, etc..
    $app->initialize();

    return $app;
});
