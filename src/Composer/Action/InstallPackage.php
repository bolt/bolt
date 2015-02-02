<?php

namespace Bolt\Composer\Action;

use Composer\Installer;
use Silex\Application;

/**
 * Composer package install class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class InstallPackage
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
     * Install packages
     *
     * @return integer 0 on success or a positive error code on failure
     */
    public function execute()
    {
        $composer = $this->app['extend.manager']->getComposer();
        $io = $this->app['extend.manager']->getIO();

        $install = Installer::create($io, $composer);
        $config = $composer->getConfig();
        $optimize = $config->get('optimize-autoloader');

        $preferSource = false;
        $preferDist = true;

        switch ($config->get('preferred-install')) {
            case 'source':
                $preferSource = true;
                break;
            case 'dist':
                $preferDist = true;
                break;
            case 'auto':
            default:
                // noop
                break;
        }

        if ($config->get('prefer-source') || $config->get('prefer-dist')) {
            $preferSource = $config->get('prefer-source');
            $preferDist = $config->get('prefer-dist');
        }

        $install
            ->setDryRun($this->options['dry-run'])
            ->setVerbose($this->options['verbose'])
            ->setPreferSource($preferSource)
            ->setPreferDist($preferDist)
            ->setDevMode(!$this->options['no-dev'])
            ->setDumpAutoloader(!$this->options['no-autoloader'])
            ->setRunScripts(!$this->options['no-scripts'])
            ->setOptimizeAutoloader($optimize)
            ->setIgnorePlatformRequirements($this->options['ignore-platform-reqs'])
            ->setUpdate(true);

        return $install->run();
    }
}
