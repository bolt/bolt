<?php

namespace Bolt;

use Bolt\Configuration\Composer;
use Bolt\Configuration\PathResolverFactory;
use Bolt\Configuration\ResourceManager;
use Bolt\Configuration\Standard;
use Bolt\Extension\ExtensionInterface;
use LogicException;
use Silex;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

/**
 * Handles creating the Application with .bolt.[yml|php] configuration.
 *
 * This does not handle autoloading.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Bootstrap
{
    /**
     * Create the application.
     *
     * @param string $rootPath
     *
     * @return Silex\Application
     */
    public static function run($rootPath)
    {
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

        $rootPath = Path::canonicalize($rootPath);

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

        $pathResolverFactoryFactory = \Pimple::share(function () use ($rootPath, $config) {
            $pathResolverFactory = new PathResolverFactory();
            $pathResolverFactory->setRootPath($rootPath);
            $pathResolverFactory->addPaths((array) $config['paths']);

            return $pathResolverFactory;
        });

        $resourcesClass = func_num_args() > 1 ? func_get_arg(1) : Standard::class;
        $resourcesFactory = \Pimple::share(function () use ($config, $resourcesClass, $rootPath, $pathResolverFactoryFactory) {
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

                /** @var \Bolt\Configuration\ResourceManager $resources */
                if ($passPathResolverFactory) {
                    $pathResolverFactory = $pathResolverFactoryFactory(null);
                    $resources = new $resourcesClass($rootPath, null, $pathResolverFactory);
                } else {
                    $resources = new $resourcesClass($rootPath);
                }
            }

            // Set any non-standard paths
            foreach ((array) $config['paths'] as $name => $path) {
                $resources->setPath($name, $path, false);
            }

            $resources->verify();

            return $resources;
        });

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
            'resources.bootstrap'   => $config['resources'] !== null,
        ]);

        foreach ((array) $config['services'] as $service) {
            $params = [];
            if (is_array($service)) {
                $params = reset($service);
                $service = key($service);
            }

            if (is_string($service) && is_a($service, Silex\ServiceProviderInterface::class, true)) {
                $service = new $service();
            }
            if ($service instanceof Silex\ServiceProviderInterface) {
                $app->register($service, $params);
            }
        }

        if (!$config['extensions']) {
            return $app;
        }
        if (!isset($app['extensions'])) {
            throw new LogicException('Provided application object does not contain an extension service, but extensions are defined in bootstrap.');
        }

        $app['extensions'] = $app->share(
            $app->extend(
                'extensions',
                function ($extensions) use ($config) {
                    foreach ((array) $config['extensions'] as $extensionClass) {
                        if (is_string($extensionClass)) {
                            if (!class_exists($extensionClass)) {
                                throw new LogicException(sprintf('Extension class name "%s" is defined in .bolt.yml or .bolt.php, but the class name is misspelled or not loadable by Composer.', $extensionClass));
                            }
                            if (!is_a($extensionClass, ExtensionInterface::class, true)) {
                                throw new LogicException(sprintf('Extension class "%s" must implement %s', $extensionClass, ExtensionInterface::class));
                            }
                            $extensionClass = new $extensionClass();
                        }
                        if (!$extensionClass instanceof ExtensionInterface) {
                            throw new LogicException(sprintf('Extension class "%s" must be an instance of %s', get_class($extensionClass), ExtensionInterface::class));
                        }
                        $extensions->add($extensionClass);
                    }

                    return $extensions;
                }
            )
        );

        return $app;
    }
}
