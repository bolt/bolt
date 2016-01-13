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
        $config = $composer->getConfig();

        $optimize = $this->getOptions()->optimizeAutoloader() || $config->get('optimize-autoloader');
        $authoritative = $this->getOptions()->classmapAuthoritative() || $config->get('classmap-authoritative');

        $install = Installer::create($io, $composer)
            ->setDryRun($this->getOptions()->dryRun())
            ->setVerbose($this->getOptions()->verbose())
            ->setPreferSource($this->getOptions()->preferSource())
            ->setPreferDist($this->getOptions()->preferDist())
            ->setDevMode(!$this->getOptions()->noDev())
            ->setDumpAutoloader(!$this->getOptions()->noAutoloader())
            ->setRunScripts(!$this->getOptions()->noScripts())
            ->setOptimizeAutoloader($optimize)
            ->setClassMapAuthoritative($authoritative)
            ->setIgnorePlatformRequirements($this->getOptions()->ignorePlatformReqs())
        ;

        try {
            return $install->run();
        } catch (\Exception $e) {
            $msg = sprintf('%s recieved an error from Composer: %s in %s::%s', __METHOD__, $e->getMessage(), $e->getFile(), $e->getLine());
            $this->app['logger.system']->critical($msg, ['event' => 'exception', 'exception' => $e]);
            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
