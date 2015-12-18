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

        $io = $this->getIO();

        /** @var \Bolt\Filesystem\Handler\JsonFile $jsonFile */
        $jsonFile = $this->app['filesystem']->get($this->getOption('composerjson'));
        $composerJson = $composerBackup = $jsonFile->parse();
        $type = $this->getOption('dev') ? 'require-dev' : 'require';

        // Remove packages from JSON
        foreach ($composerJson[$type] as $package) {
            unset($composerJson[$type][$package]);
        }

        // Reload Composer config
        $composer = $this->resetComposer();
        // Create the installer
        $install = Installer::create($io, $composer)
            ->setVerbose($this->getOption('verbose'))
            ->setDevMode(!$this->getOption('updatenodev'))
            ->setUpdate(true)
            ->setUpdateWhitelist($packages)
            ->setWhitelistDependencies($this->getOption('updatewithdependencies'))
            ->setIgnorePlatformRequirements($this->getOption('ignoreplatformreqs'))
        ;

        try {
            $status = $install->run();
        } catch (\Exception $e) {
            $msg = __CLASS__ . '::' . __FUNCTION__ . ' recieved an error from Composer: ' . $e->getMessage() . ' in ' . $e->getFile() . '::' . $e->getLine();
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
