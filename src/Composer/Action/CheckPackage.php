<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Composer\Package\PackageInterface;

/**
 * Checks for installable, or upgradeable packages.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class CheckPackage extends BaseAction
{
    /**
     * Run a check for package(s).
     *
     * @throws PackageManagerException
     *
     * @return array
     */
    public function execute()
    {
        $packages = ['updates' => [], 'installs' => []];

        // Get known installed packages
        $rootPackage = $this->app['extend.action']['show']->execute('installed');

        /** @var \Bolt\Filesystem\Handler\JsonFile $jsonFile */
        $jsonFile = $this->getOptions()->composerJson();

        // Get the packages that a set as "required" in the JSON file
        $json = $jsonFile->parse();
        $jsonRequires = $json['require'];

        // Find the packages that are NOT part of the root install yet and mark
        // them as pending installs
        if (!empty($jsonRequires)) {
            /**
             * @var string $packageName
             * @var string $packageVersion
             */
            foreach ($jsonRequires as $packageName => $packageVersion) {
                if (array_key_exists($packageName, $rootPackage)) {
                    continue;
                }
                try {
                    $remote = $this->findBestVersionForPackage($packageName, $packageVersion, true);
                } catch (\Exception $e) {
                    $msg = sprintf('%s recieved an error from Composer: %s in %s::%s', __METHOD__, $e->getMessage(), $e->getFile(), $e->getLine());
                    $this->app['logger.system']->critical($msg, ['event' => 'exception', 'exception' => $e]);

                    throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
                }

                // If a 'best' version is found, and there is a version mismatch then
                // propose as an update. Making the assumption that Composer isn't
                // going to offer us an older version.
                if (is_array($remote)) {
                    $packages['installs'][] = $remote;
                }
            }
        }

        /**
         * For installed packages, see if there is a valid update.
         *
         * @var string $packageName
         * @var array  $targetPackageVersion
         */
        foreach ($rootPackage as $packageName => $packageVersion) {
            try {
                $remote = $this->findBestVersionForPackage($packageName, $targetPackageVersion, true);
            } catch (\Exception $e) {
                $msg = sprintf('%s recieved an error from Composer: %s in %s::%s', __METHOD__, $e->getMessage(), $e->getFile(), $e->getLine());
                $this->app['logger.system']->critical($msg, ['event' => 'exception', 'exception' => $e]);

                throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
            }

            /** @var PackageInterface $package */
            $package = $remote['package'];

            // If a 'best' version is found, and there is a version mismatch then
            // propose as an update. Making the assumption that Composer isn't
            // going to offer us an older version.
            if (is_array($remote) && ($package->getVersion() !== $package->getVersion())) {
                $packages['updates'][] = $remote;
            }
        }

        return $packages;
    }
}
