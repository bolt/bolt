<?php

namespace Bolt\Provider;

use Bolt\Composer\Action;
use Bolt\Composer\EventListener\BufferIOListener;
use Bolt\Composer\JsonManager;
use Bolt\Composer\PackageManager;
use Bolt\Composer\Satis;
use Bolt\Extension\Manager;
use Composer\IO\BufferIO;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Silex\Api\BootableProviderInterface;

/**
 * 1st phase: Registers our services. Registers extensions on boot.
 * 2nd phase: Boots extensions on boot.
 */
class ExtensionServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    /** @var bool */
    private $firstPhase;

    /**
     * Constructor.
     *
     * @param bool $firstPhase
     */
    public function __construct($firstPhase = true)
    {
        $this->firstPhase = $firstPhase;
    }

    public function register(Container $app)
    {
        if (!$this->firstPhase) {
            return;
        }

        $app['extensions'] = 
            function ($app) {
                $loader = new Manager(
                    $app['filesystem']->getFilesystem('extensions'),
                    $app['filesystem']->getFilesystem('web'),
                    $app['logger.flash'],
                    $app['config']
                );
                $loader->addManagedExtensions();

                return $loader;
            }
        ;

        $app['extensions.stats'] = 
            function ($app) {
                $stats = new Satis\StatService($app['guzzle.client'], $app['logger.system'], $app['extend.site']);

                return $stats;
            }
        ;

        $app['extend.site'] = function ($app) {
            return $app['config']->get('general/extensions/site', 'https://market.bolt.cm/');
        };
        $app['extend.repo'] = function ($app) {
            return $app['extend.site'] . 'list.json';
        };
        $app['extend.urls'] = [
            'list' => 'list.json',
            'info' => 'info.json',
        ];

        $app['extend.online'] = false;
        $app['extend.enabled'] = function ($app) {
            return $app['config']->get('general/extensions/enabled', true);
        };
        $app['extend.writeable'] = 
            function () use ($app) {
                $extensionsPath = $app['path_resolver']->resolve('extensions');

                return is_dir($extensionsPath) && is_writable($extensionsPath);
            }
        ;

        $app['extend.manager'] = 
            function ($app) {
                return new PackageManager($app);
            }
        ;

        $app['extend.manager.json'] = 
            function ($app) {
                return new JsonManager($app);
            }
        ;

        $app['extend.listener'] = 
            function ($app) {
                return new BufferIOListener($app['extend.manager'], $app['logger.system']);
            }
        ;

        $app['extend.info'] = 
            function ($app) {
                return new Satis\QueryService($app['guzzle.client'], $app['extend.site'], $app['extend.urls']);
            }
        ;

        // Actions
        $app['extend.action'] = 
            function (Application $app) {
                return new \Pimple(
                    [
                        // @codingStandardsIgnoreStart
                        'autoload'  => function () use ($app) { return new Action\DumpAutoload($app); },
                        'check'     => function () use ($app) { return new Action\CheckPackage($app); },
                        'depends'   => function () use ($app) { return new Action\DependsPackage($app); },
                        'install'   => function () use ($app) { return new Action\InstallPackage($app); },
                        'prohibits' => function () use ($app) { return new Action\ProhibitsPackage($app); },
                        'remove'    => function () use ($app) { return new Action\RemovePackage($app); },
                        'require'   => function () use ($app) { return new Action\RequirePackage($app); },
                        'search'    => function () use ($app) { return new Action\SearchPackage($app); },
                        'show'      => function () use ($app) { return new Action\ShowPackage($app); },
                        'update'    => function () use ($app) { return new Action\UpdatePackage($app); },
                        // @codingStandardsIgnoreEnd
                    ]
                );
            }
        ;

        $app['extend.action.io'] = 
            function () {
                return new BufferIO();
            }
        ;

        $app['extend.action.options'] = 
            function ($app) {
                $composerJson = $app['filesystem']->getFile('extensions://composer.json');
                $composerOverrides = $app['config']->get('general/extensions/composer', []);

                return new Action\Options($composerJson, $composerOverrides, true);
            }
        ;
    }

    public function boot(Application $app)
    {
        if ($this->firstPhase) {
            $app['extensions']->register($app);
        } else {
            $app['extensions']->boot($app);
            }
        }
}
