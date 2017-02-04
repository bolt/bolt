<?php

namespace Bolt;

use Bolt\Configuration\Composer;
use Bolt\Configuration\PathResolverFactory;
use Bolt\Configuration\ResourceManager;
use Bolt\Configuration\Standard;
use Bolt\Debug\ShutdownHandler;
use Bolt\Exception\BootException;
use Bolt\Extension\ExtensionInterface;
use LogicException;
use Pimple;
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
        'services'    => [],
        'extensions'  => [],
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

    $pathResolverFactoryFactory = function () use ($rootPath, $config) {
        $pathResolverFactory = new PathResolverFactory();
        $pathResolverFactory->setRootPath($rootPath);
        $pathResolverFactory->addPaths((array) $config['paths']);

        return $pathResolverFactory;
    };

    $resourcesFactory = function () use ($config, $resourcesClass, $rootPath, $pathResolverFactoryFactory) {
        // Use resources from config, or instantiate the class based on mapping above.
        if ($config['resources'] instanceof ResourceManager) {
            $resources = $config['resources'];
        } else {
            if ($config['resources'] !== null && is_a($config['resources'], ResourceManager::class, true)) {
                $resourcesClass = $config['resources'];
            }

            $passPathResolverFactory = false;
            if ($resourcesClass === Composer::class || $resourcesClass === Standard::class) {
                $passPathResolverFactory = true;
            } else {
                $r = (new \ReflectionClass($resourcesClass))->getConstructor();
                if ($r->getNumberOfParameters() > 2 && $r->getParameters()[2]->getClass()->name === PathResolverFactory::class) {
                    $passPathResolverFactory = true;
                }
            }

            if ($passPathResolverFactory) {
                $pathResolverFactory = $pathResolverFactoryFactory(null);
                $resources = new $resourcesClass($rootPath, null, $pathResolverFactory);
            } else {
                $resources = new $resourcesClass($rootPath);
            }
        }
        /** @var \Bolt\Configuration\ResourceManager $resources */

        // Set any non-standard paths
        foreach ((array) $config['paths'] as $name => $path) {
            $resources->setPath($name, $path);
        }

        $resources->verify();

        return $resources;
    };

    // If resources is already initialized, go ahead and customize it now.
    if ($config['resources'] instanceof ResourceManager) {
        $resourcesFactory = $resourcesFactory(null);
    }

    // Create the 'Bolt application'
    $appClass = Application::class;
    if ($config['application'] !== null && is_a($config['application'], Silex\Application::class, true)) {
        $appClass = $config['application'];
    }
    /** @var Silex\Application $app */
    $app = new $appClass([
        'resources'             => $resourcesFactory,
        'path_resolver_factory' => $pathResolverFactoryFactory,
        'path_resolver.root'    => $rootPath,
        'path_resolver.paths'   => (array) $config['paths'],
    ]);

    foreach ((array) $config['services'] as $service) {
        $params = [];
        if (is_array($service)) {
            $key = key($service);
            $params = $service[$key];
            $service = $key;
        }

        if (is_string($service) && is_a($service, Pimple\ServiceProviderInterface::class, true)) {
            $service = new $service();
        }
        if ($service instanceof Pimple\ServiceProviderInterface) {
            $app->register($service, $params);
        }

    }

    $app['extensions'] = 
        $app->extend('extensions', function ($extensions) use ($config) {
            foreach ((array)$config['extensions'] as $extensionClass) {
                if (is_string($extensionClass) && class_exists($extensionClass, true)) {
                    $extensionClass = new $extensionClass();
                } else {
                    throw new LogicException(sprintf('Unable to load extension class %s', $extensionClass));
                }
                if ($extensionClass instanceof ExtensionInterface) {
                    $extensions->add($extensionClass);
                }
            }

            return $extensions;
        })
    ;

    return $app;
});
