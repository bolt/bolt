<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Composer\Installer;
use Silex\Application;

/**
 * Composer package install class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class InstallPackage extends BaseAction
{
    /**
     * Install packages.
     *
     * @throws \Bolt\Exception\PackageManagerException
     *
     * @return int 0 on success or a positive error code on failure
     */
    public function execute()
    {
        /** @var $composer \Composer\Composer */
        $composer = $this->getComposer();
        $io = $this->getIO();

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

        try {
            $install
                ->setDryRun($this->getOption('dryrun'))
                ->setVerbose($this->getOption('verbose'))
                ->setPreferSource($preferSource)
                ->setPreferDist($preferDist)
                ->setDevMode(!$this->getOption('nodev'))
                ->setDumpAutoloader(!$this->getOption('noautoloader'))
                ->setRunScripts(!$this->getOption('noscripts'))
                ->setOptimizeAutoloader($optimize)
                ->setIgnorePlatformRequirements($this->getOption('ignoreplatformreqs'))
                ->setUpdate(true);

            return $install->run();
        } catch (\Exception $e) {
            $msg = __CLASS__ . '::' . __FUNCTION__ . ' recieved an error from Composer: ' . $e->getMessage() . ' in ' . $e->getFile() . '::' . $e->getLine();
            $this->app['logger.system']->critical($msg, ['event' => 'exception', 'exception' => $e]);
            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
