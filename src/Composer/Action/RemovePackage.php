<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Composer\Installer;

/**
 * Composer remove package class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class RemovePackage extends BaseAction
{
    /**
     * Remove packages from the root install.
     *
     * @param  $packages array Indexed array of package names to remove
     *
     * @throws \Bolt\Exception\PackageManagerException
     *
     * @return int 0 on success or a positive error code on failure
     */
    public function execute(array $packages)
    {
        if (empty($packages)) {
            throw new PackageManagerException('No package specified for removal');
        }

        /** @var \Bolt\Filesystem\Handler\JsonFile $jsonFile */
        $jsonFile = $this->getOptions()->composerJson();
        $composerJson = $composerBackup = $jsonFile->parse();
        $type = $this->getOptions()->dev() ? 'require-dev' : 'require';

        // Remove packages from JSON
        foreach ($packages as $package) {
            unset($composerJson[$type][$package]);
        }
        if (empty($composerJson[$type])) {
            unset($composerJson[$type]);
        }
        $jsonFile->dump($composerJson);

        $io = $this->getIO();
        // Reload Composer config
        $composer = $this->resetComposer();
        // Create the installer
        $install = Installer::create($io, $composer)
            ->setVerbose($this->getOptions()->verbose())
            ->setDevMode(!$this->getOptions()->updateNoDev())
            ->setUpdate(true)
            ->setUpdateWhitelist($packages)
            ->setWhitelistDependencies($this->getOptions()->updateWithDependencies())
            ->setIgnorePlatformRequirements($this->getOptions()->ignorePlatformReqs())
            ->setRunScripts(!$this->getOptions()->noScripts())
        ;

        try {
            $status = $install->run();
        } catch (\Exception $e) {
            $msg = sprintf('%s recieved an error from Composer: %s in %s::%s', __METHOD__, $e->getMessage(), $e->getFile(), $e->getLine());
            $this->app['logger.system']->critical($msg, ['event' => 'exception', 'exception' => $e]);

            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }

        if ($status !== 0) {
            // Write out old JSON file
            $jsonFile->dump($composerBackup);
        } else {
            $jsonFile->dump($composerJson);
        }

        return $status;
    }
}
