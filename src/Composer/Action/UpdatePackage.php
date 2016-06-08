<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Composer\Installer;

/**
 * Composer update package class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class UpdatePackage extends BaseAction
{
    /**
     * Update packages.
     *
     * @param  $packages array Indexed array of package names to update
     * @param  $options  array [Optional] changed option set
     *
     * @throws \Bolt\Exception\PackageManagerException
     *
     * @return int 0 on success or a positive error code on failure
     */
    public function execute(array $packages = [], array $options = [])
    {
        // Handle passed in options
        foreach ($options as $key => $value) {
            $this->getOptions()->set($key, $value);
        }

        /** @var \Composer\Composer $composer */
        $composer = $this->getComposer();
        $io = $this->getIO();
        $install = Installer::create($io, $composer);
        try {
            $install
                ->setDryRun($this->getOptions()->dryRun())
                ->setVerbose($this->getOptions()->verbose())
                ->setPreferSource($this->getOptions()->preferSource())
                ->setPreferDist($this->getOptions()->preferDist())
                ->setDevMode(!$this->getOptions()->noDev())
                ->setDumpAutoloader(!$this->getOptions()->noAutoloader())
                ->setRunScripts(!$this->getOptions()->noScripts())
                ->setOptimizeAutoloader($this->getOptions()->optimizeAutoloader())
                ->setUpdate(true)
                ->setUpdateWhitelist($packages)
                ->setWhitelistDependencies($this->getOptions()->withDependencies())
                ->setIgnorePlatformRequirements($this->getOptions()->ignorePlatformReqs())
                ->setPreferStable($this->getOptions()->preferStable())
                ->setPreferLowest($this->getOptions()->preferLowest())
                ->setRunScripts(!$this->getOptions()->noScripts())
            ;

            return $install->run();
        } catch (\Exception $e) {
            $msg = sprintf('%s recieved an error from Composer: %s in %s::%s', __METHOD__, $e->getMessage(), $e->getFile(), $e->getLine());
            $this->app['logger.system']->critical($msg, ['event' => 'exception', 'exception' => $e]);

            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
