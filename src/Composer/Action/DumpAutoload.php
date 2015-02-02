<?php

namespace Bolt\Composer\Action;

use Silex\Application;

/**
 * Composer autoloader creation class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class DumpAutoload
{
    /**
     * @var Silex\Application
     */
    private $app;

    /**
     * @param $app Silex\Application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Dump autoloaders
     */
    public function execute()
    {
        $composer = $this->app['extend.manager']->getComposer();
        $installationManager = $composer->getInstallationManager();
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
        $package = $composer->getPackage();
        $config = $composer->getConfig();

        if ($this->app['extend.manager']->getOption('optimizeautoloader')) {
            // Generating optimized autoload files
        } else {
            // Generating autoload files
        }

        $generator = $composer->getAutoloadGenerator();
        $generator->setDevMode(!$this->app['extend.manager']->getOption('nodev'));
        $generator->dump($config, $localRepo, $package, $installationManager, 'composer', $this->app['extend.manager']->getOption('optimizeautoloader'));
    }
}
