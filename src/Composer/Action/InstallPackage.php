<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Composer\Installer;

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

        // Set preferred install method
        $prefer = $this->getPreferedTarget($config->get('preferred-install'));

        try {
            $install
                ->setDryRun($this->getOption('dryrun'))
                ->setVerbose($this->getOption('verbose'))
                ->setPreferSource($prefer['source'])
                ->setPreferDist($prefer['dist'])
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
