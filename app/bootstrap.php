<?php

namespace Bolt;

use Bolt\Configuration\Composer;
use Bolt\Configuration\ResourceManager;
use Bolt\Configuration\Standard;
use Bolt\Debug\ShutdownHandler;
use Bolt\Exception\BootException;
use Silex;
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
 * @throws BootException
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

    $error = true;
    foreach ($autodetectionMappings as $autoloadPath => list($resourcesClass, $rootPath)) {
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

    // Register handlers early
    ShutdownHandler::register();

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
        'application' => null,
        'resources'   => null,
        'paths'       => [],
    ];

    if (file_exists($rootPath . '/.bolt.yml')) {
        $yaml = Yaml::parse(file_get_contents($rootPath . '/.bolt.yml')) ?: [];
        $config = array_replace_recursive($config, $yaml);
    } elseif (file_exists($rootPath . '/.bolt.php')) {
        $php = include $rootPath . '/.bolt.php';
    }

    // An extra handler if a PHP bootstrap is provided, allow the bootstrap file to return
    // a pre-initialized Bolt Application rather than the config array.
    if (isset($php) && is_array($php)) {
        $config = array_replace_recursive($config, $php);
    } elseif (isset($php) && $php instanceof Silex\Application) {
        return $php;
    }

    // If application object is provided, assume it is ready to go.
    if ($config['application'] instanceof Silex\Application) {
        return $config['application'];
    }

    // Use resources from config, or instantiate the class based on mapping above.
    if ($config['resources'] instanceof ResourceManager) {
        $resources = $config['resources'];
    } else {
        if ($config['resources'] !== null && is_a($config['resources'], ResourceManager::class)) {
            $resourcesClass = $config['resources'];
        }

        /** @var \Bolt\Configuration\ResourceManager $resources */
        $resources = new $resourcesClass($rootPath);
    }

    // Set any non-standard paths
    foreach ((array) $config['paths'] as $name => $path) {
        $resources->setPath($name, $path);
    }
    if (!file_exists($resources->getPath('web')) && $resources instanceof Composer) {
        BootException::earlyExceptionMissingLoaderConfig();
    }

    /** @var \Bolt\Configuration\ResourceManager $config */
    $resources->verify();

    // Create the 'Bolt application'
    $appClass = Application::class;
    if ($config['application'] !== null && is_a($config['application'], Silex\Application::class, true)) {
        $appClass = $config['application'];
    }
    $app = new $appClass(['resources' => $resources]);

    // Initialize the 'Bolt application': Set up all routes, providers, database, templating, etc..
    if (method_exists($app, 'initialize')) {
        $app->initialize();
    }

    return $app;
});
