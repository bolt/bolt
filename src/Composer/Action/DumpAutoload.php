<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Silex\Application;

/**
 * Composer autoloader creation class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class DumpAutoload
{
    /**
     * @var \Silex\Application
     */
    private $app;

    /**
     * @param $app \Silex\Application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Dump autoloaders.
     */
    public function execute()
    {
        /** @var $composer \Composer\Composer */
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

        try {
            $generator = $composer->getAutoloadGenerator();
            $generator->setDevMode(!$this->app['extend.manager']->getOption('nodev'));
            $generator->dump($config, $localRepo, $package, $installationManager, 'composer', $this->app['extend.manager']->getOption('optimizeautoloader'));
        } catch (\Exception $e) {
            $msg = __CLASS__ . '::' . __FUNCTION__ . ' recieved an error from Composer: ' . $e->getMessage() . ' in ' . $e->getFile() . '::' . $e->getLine();
            $this->app['logger.system']->critical($msg, array('event' => 'exception', 'exception' => $e));

            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
